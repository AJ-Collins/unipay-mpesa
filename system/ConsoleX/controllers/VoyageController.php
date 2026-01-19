<?php

namespace ConsoleX\controllers;

use Yii;
use yii\db\Query;
use helpers\auth\Item;
use yii\helpers\Console;
use yii\console\ExitCode;
use iam\hooks\AuthConfigs;
use helpers\auth\AuthManager;
use iam\models\static\rbac\AuthItem;
use yii\console\controllers\MigrateController;

/**
 * VoyageController handles migration and RBAC updates.
 */
class VoyageController extends MigrateController
{
    public $newFileMode = 0777;
    public $interactive = false;
    public $tenant; // optional tenant argument

    /**
     * Run migrations then update RBAC.
     *
     * @param int $limit
     * @return int
     */
    public function actionUp($limit = 0)
    {
        // Run default migration behavior
        $result = parent::actionUp($limit);

        // Refresh DB schema (in case migrations changed RBAC tables)
        if (Yii::$app->has('db')) {
            try {
                Yii::$app->db->schema->refresh();
            } catch (\Throwable $e) {
                $this->stdout("Warning: failed to refresh DB schema: " . $e->getMessage() . PHP_EOL, Console::FG_YELLOW);
            }
        }

        // Run RBAC update (defensive: catch exceptions)
        try {
            $this->updateRbac();
        } catch (\Throwable $e) {
            $this->stdout("RBAC update failed: " . $e->getMessage() . PHP_EOL, Console::FG_RED);
            // still return parent result (migration outcome) but indicate failure
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return $result === ExitCode::OK ? ExitCode::OK : $result;
    }

    /**
     * Orchestrates RBAC updates.
     */
    protected function updateRbac(): void
    {
        $authManager = Yii::$app->getAuthManager();

        if (!($authManager instanceof AuthManager)) {
            $this->stdout("AuthManager is not configured to use DB-backed AuthManager. Skipping RBAC update.\n", Console::FG_RED);
            return;
        }

        $this->stdout("Updating RBAC...\n", Console::FG_CYAN);

        $auth = AuthConfigs::authManager(); // our wrapper/alias to manager

        if ($auth === null) {
            $this->stdout("AuthConfigs::authManager() returned null. Aborting RBAC update.\n", Console::FG_RED);
            return;
        }
        // 1) Ensure default roles and protected groups
        $this->stdout("*** Ensuring rules...\n", Console::FG_YELLOW);
        $this->ensureRules($auth);
        // 1) Ensure default roles and protected groups
        $this->stdout("*** Ensuring default roles & protected groups...\n", Console::FG_YELLOW);
        $this->ensureDefaultRolesAndGroups($auth);
        // 2) Ensure role hierarchy children (su -> others)
        $this->stdout("*** Ensuring role hierarchy...\n", Console::FG_YELLOW);
        $this->ensureRoleHierarchy($auth);
        // 3) Remove obsolete permissions
        $this->stdout("*** Checking obsolete permissions...\n", Console::FG_YELLOW);
        $this->removeObsoletePermissions($auth);
        // 4) Scan & mount new permissions
        $this->stdout("*** Scanning for new permissions...\n", Console::FG_YELLOW);
        $scan = (new AuthItem(null))->scanPermissions();
        $this->mountNewPermissions($auth, $scan);

        $this->stdout("RBAC updated successfully.\n", Console::FG_CYAN);
    }

    protected function ensureRules($auth)
    {
        $rulesPath = Yii::getAlias('@modules');
        if (!is_dir($rulesPath)) {
            $this->stdout("Modules path not found: {$rulesPath}\n", \yii\helpers\Console::FG_RED);
            return ExitCode::DATAERR;
        }
        $fileRules = [];
        $stats = ['found' => 0, 'synced' => 0, 'removed' => 0];
        // 1. Automatically scan all modules for /hooks/rules/*.php
        foreach (new \DirectoryIterator($rulesPath) as $module) {
            if ($module->isDot() || !$module->isDir()) {
                continue;
            }

            // Automatic module name from folder
            $moduleName = $module->getFilename();

            $rulesDir = $module->getPathname() . '/hooks/rules';
            if (!is_dir($rulesDir)) {
                continue;
            }
            foreach (glob("{$rulesDir}/*.php") as $file) {
                $className = $this->getClassNameFromFile($file);
                if (!$className || !class_exists($className)) {
                    continue;
                }
                try {
                    /** @var \yii\rbac\Rule $rule */
                    $rule = new $className();
                    if (empty($rule->name)) {
                        continue;
                    }
                    // Auto-set module if not defined in rule
                    if (!isset($rule->module)) {
                        $rule->module = $moduleName;
                    }
                    $rule->hash = hash_file('sha256', $file);
                    $fileRules[$rule->name] = $rule;
                    $stats['found']++;
                } catch (\Exception $e) {
                    $this->stdout("Error loading {$file}: {$e->getMessage()}\n", \yii\helpers\Console::FG_RED);
                }
            }
        }
        // 2. Get current DB rule names
        $dbRuleNames = array_keys($auth->getRules());
        // 3. Sync: replace or add
        foreach ($fileRules as $name => $ruleData) {
            $exists = in_array($name, $dbRuleNames);
            if (!$exists) {
                // NEW RULE
                $auth->add($ruleData);
                $this->updateRuleMeta($auth, $ruleData, $ruleData->module, $ruleData->hash);
                $this->stdout("    > Added {$name}\n", Console::FG_GREEN);
                $stats['synced']++;
                continue;
            } else {
                // Check for change
                $dbHash = Yii::$app->db->createCommand(
                    "SELECT file_hash FROM {$auth->ruleTable} WHERE name = :name",
                    [':name' => $name]
                )->queryScalar();

                if ($dbHash !== $ruleData->hash) {
                    // UPDATE ONLY IF FILE CHANGED
                    $auth->update($name, $rule);
                    $this->updateRuleMeta($auth, $ruleData, $ruleData->module, $ruleData->hash);
                    $this->stdout("    > Updated {$name}\n", Console::FG_YELLOW);
                    $stats['synced']++;
                }
            }
        }

        // 4. Remove obsolete
        foreach ($dbRuleNames as $name) {
            if (!isset($fileRules[$name])) {
                Yii::$app->db->createCommand()
                    ->delete($auth->ruleTable, ['name' => $name])
                    ->execute();
                $this->stdout("    > Removed obsolete rule: {$name}\n", \yii\helpers\Console::FG_RED);
                $stats['removed']++;
            }
        }
        $this->stdout(
            "Rule sync complete: Found {$stats['found']}, Synced {$stats['synced']}, Removed {$stats['removed']}.\n",
            \yii\helpers\Console::FG_CYAN
        );
        return ExitCode::OK;
    }
    private function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        if (
            preg_match('/namespace\s+([^;]+);/', $content, $ns) &&
            preg_match('/class\s+(\w+)[^\w]/', $content, $class)
        ) {
            return trim($ns[1]) . '\\' . $class[1];
        }
        return null;
    }
    private function updateRuleMeta($auth, $rule, string $module, string $hash): void
    {
        Yii::$app->db->createCommand()->update(
            $auth->ruleTable,
            [
                'description' => $rule->description ?? null,
                'module'      => $module,
                'file_hash'   => $hash,
                'rule_title'     => $rule->rule_title ?? null,
            ],
            ['name' => $rule->name]
        )->execute();
    }


    /**
     * Ensure default roles and protected groups exist.
     *
     * @param mixed $auth
     * @return void
     */
    protected function ensureDefaultRolesAndGroups($auth): void
    {
        // Defensive: check the protectedRoles structure
        if (empty($auth->protectedRoles) || !isset($auth->protectedRoles['role'])) {
            $this->stdout("No protectedRoles defined in auth manager config. Skipping default role creation.\n", Console::FG_YELLOW);
            return;
        }

        // Only create default roles if su children are empty (no children mounted yet)
        $suChildren = method_exists($auth, 'getChildren') ? $auth->getChildren('su') : null;
        if (!empty($suChildren)) {
            $this->stdout("Default roles already mounted.\n", Console::FG_CYAN);
            return;
        }

        foreach ($auth->protectedRoles['role'] as $key => $value) {
            try {
                $model = new AuthItem(null);
                $model->type = Item::TYPE_ROLE;
                $model->name = $key;
                $model->description = $value;
                $model->save(false);
                $this->stdout("    > Role: {$value} ({$key}) created.\n", Console::FG_GREEN);
            } catch (\Throwable $e) {
                $this->stdout("    > Failed to create role {$key}: " . $e->getMessage() . "\n", Console::FG_RED);
                continue;
            }

            // Create protected groups when creating 'su'
            if ($key === 'su' && !empty($auth->protectedRoles['group'])) {
                foreach ($auth->protectedRoles['group'] as $groupKey => $groupValue) {
                    try {
                        $existing = method_exists($auth, 'getGroup') ? $auth->getGroup($groupKey) : null;
                        if ($existing !== null) {
                            $this->stdout("    > Group: {$groupKey} already exists.\n", Console::FG_CYAN);
                            continue;
                        }

                        $gmodel = new AuthItem(null);
                        $gmodel->type = Item::TYPE_GROUP;
                        $gmodel->name = $groupKey;
                        $gmodel->description = $groupValue;
                        $gmodel->data = 'This is a protected group and cannot be deleted or modified.';
                        $gmodel->save(false);
                        $this->stdout("    > Group: {$groupKey} created.\n", Console::FG_GREEN);
                    } catch (\Throwable $e) {
                        $this->stdout("    > Failed to create group {$groupKey}: " . $e->getMessage() . "\n", Console::FG_RED);
                        continue;
                    }
                }

                // Add children and assign 'sa' to first user if available
                try {
                    $saGroup = method_exists($auth, 'getGroup') ? $auth->getGroup('sa') : null;
                    if ($saGroup !== null) {
                        $saItem = new AuthItem($saGroup);
                        // attach 'su' as child of 'sa' (defensive call)
                        try {
                            $saItem->addChildren(['su']);
                        } catch (\Throwable $e) {
                            // best-effort: ignore
                        }

                        // find the first user id (use consistent column 'id')
                        $id = (new Query())->select('user_id')->from('users')->orderBy('user_id ASC')->scalar();

                        if ($id !== false && $id !== null) {
                            if (method_exists($auth, 'assign')) {
                                $auth->assign($saGroup, $id);
                                $this->stdout("    > Assigned 'sa' group to user id {$id}.\n", Console::FG_GREEN);
                            }
                        } else {
                            $this->stdout("    > No users found to assign 'sa' group.\n", Console::FG_YELLOW);
                        }
                    } else {
                        $this->stdout("    > 'sa' group not found; skipping assignment.\n", Console::FG_YELLOW);
                    }
                } catch (\Throwable $e) {
                    $this->stdout("    > Failed to attach 'su' to 'sa' or assign group: " . $e->getMessage() . "\n", Console::FG_RED);
                }
            }
        }

        $this->stdout("Default roles creation step completed.\n", Console::FG_CYAN);
    }

    /**
     * Ensure su role has children (common roles).
     *
     * @param mixed $auth
     * @return void
     */
    protected function ensureRoleHierarchy($auth): void
    {
        try {
            $suRole = $auth->getRole('su');
            if ($suRole === null) {
                $this->stdout("    > 'su' role not found; skipping role hierarchy mount.\n", Console::FG_YELLOW);
                return;
            }

            $desiredChildren = ['editor', 'viewer', 'creator', 'api', 'deletor', 'restore'];

            // Fetch existing children from auth manager
            $existingChildren = array_keys($auth->getChildren('su')); // returns array of AuthItem objects keyed by name

            // Only add children that don't already exist
            $childrenToAdd = array_diff($desiredChildren, $existingChildren);

            if (empty($childrenToAdd)) {
                $this->stdout("    > All default children already assigned to 'su'; skipping.\n", Console::FG_GREEN);
                return;
            }

            foreach ($childrenToAdd as $childName) {
                $child = $auth->getRole($childName) ?? $auth->getPermission($childName);
                if ($child !== null) {
                    $auth->addChild($suRole, $child);
                } else {
                    $this->stdout("    > Child '{$childName}' not found; skipping.\n", Console::FG_YELLOW);
                }
            }

            $this->stdout("    > Mounted default children to 'su': " . implode(', ', $childrenToAdd) . ".\n", Console::FG_GREEN);
        } catch (\Throwable $e) {
            $this->stdout("    > Error mounting role hierarchy: " . $e->getMessage() . "\n", Console::FG_RED);
        }
    }

    /**
     * Remove obsolete permissions discovered by scanPermissions().
     *
     * @param mixed $auth
     * @return void
     */
    protected function removeObsoletePermissions($auth): void
    {
        try {
            $scan = (new AuthItem(null))->scanPermissions();
            if (!empty($scan['remove'])) {
                foreach ($scan['remove'] as $key => $value) {
                    try {
                        $perm = method_exists($auth, 'getPermission') ? $auth->getPermission($key) : null;
                        if ($perm !== null && method_exists($auth, 'remove')) {
                            $auth->remove($perm);
                            $this->stdout("    > Permission: {$key} removed.\n", Console::FG_GREEN);
                        } else {
                            $this->stdout("    > Permission: {$key} not found or cannot remove.\n", Console::FG_RED);
                        }
                    } catch (\Throwable $e) {
                        $this->stdout("    > Failed to remove permission {$key}: " . $e->getMessage() . "\n", Console::FG_RED);
                    }
                }
                $this->stdout(count($scan['remove']) . " obsolete permissions removed.\n", Console::FG_CYAN);
            } else {
                $this->stdout("No obsolete permissions found.\n", Console::FG_CYAN);
            }
        } catch (\Throwable $e) {
            $this->stdout("Failed to check obsolete permissions: " . $e->getMessage() . "\n", Console::FG_RED);
        }
    }

    /**
     * Mount newly discovered permissions and attach to sensible roles.
     *
     * @param mixed $auth
     * @param array $scan
     * @return void
     */
    protected function mountNewPermissions($auth, array $scan = []): void
    {
        $successful = 0;
        $failed = 0;

        if (empty($scan['add'])) {
            $this->stdout("No new permissions found.\n", Console::FG_CYAN);
            return;
        }

        $this->stdout(count($scan['add']) . " new permissions found.\n", Console::FG_CYAN);
        $this->stdout("*** Mounting new permissions...\n", Console::FG_YELLOW);

        foreach ($scan['add'] as $key => $value) {
            try {
                $model = new AuthItem(null);
                $model->type = Item::TYPE_PERMISSION;
                $model->name = $key;
                $model->description = $value;
                $start = microtime(true);

                if (!$model->save(false)) {
                    $failed++;
                    $this->stdout("    > Permission: {$model->name} failed to save.\n", Console::FG_RED);
                    continue;
                }

                $lower = $this->contains(strtolower($model->name), '-');
                if ($lower) {
                    $this->mountPermissionToRole($auth, 'api', $model->name);
                    $this->stdout("    > Permission: {$model->name} mounted to api role... done (time: " . sprintf('%.3f', microtime(true) - $start) . "s)\n", Console::FG_GREEN);
                } else {
                    $nm = strtolower($model->name);
                    if ($this->contains($nm, 'create')) {
                        $this->mountPermissionToRole($auth, 'creator', $model->name);
                        $this->stdout("    > Permission: {$model->name} mounted to creator role... done (time: " . sprintf('%.3f', microtime(true) - $start) . "s)\n", Console::FG_GREEN);
                    } elseif ($this->contains($nm, 'update') || $this->contains($nm, 'edit')) {
                        $this->mountPermissionToRole($auth, 'editor', $model->name);
                        $this->stdout("    > Permission: {$model->name} mounted to editor role... done (time: " . sprintf('%.3f', microtime(true) - $start) . "s)\n", Console::FG_GREEN);
                    } elseif ($this->contains($nm, 'list') || $this->contains($nm, 'view')) {
                        $this->mountPermissionToRole($auth, 'viewer', $model->name);
                        $this->stdout("    > Permission: {$model->name} mounted to viewer role... done (time: " . sprintf('%.3f', microtime(true) - $start) . "s)\n", Console::FG_GREEN);
                    } elseif ($this->contains($nm, 'delete') || $this->contains($nm, 'remove') || $this->contains($nm, 'destroy') || $this->contains($nm, 'trash')) {
                        $this->mountPermissionToRole($auth, 'deletor', $model->name);
                        $this->stdout("    > Permission: {$model->name} mounted to deletor role... done (time: " . sprintf('%.3f', microtime(true) - $start) . "s)\n", Console::FG_GREEN);
                    } elseif ($this->contains($nm, 'restore')) {
                        $this->mountPermissionToRole($auth, 'restore', $model->name);
                        $this->stdout("    > Permission: {$model->name} mounted to restore role... done (time: " . sprintf('%.3f', microtime(true) - $start) . "s)\n", Console::FG_GREEN);
                    } else {
                        $this->mountPermissionToRole($auth, 'su', $model->name);
                        $this->stdout("    > Permission: {$model->name} mounted to super user role... done (time: " . sprintf('%.3f', microtime(true) - $start) . "s)\n", Console::FG_GREEN);
                    }
                }

                $successful++;
            } catch (\Throwable $e) {
                $failed++;
                $this->stdout("    > Failed to mount permission {$key}: " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }

        $this->stdout("*** {$successful} permissions mounted and assigned, {$failed} permissions failed to mount.\n", Console::FG_YELLOW);
    }

    /**
     * Try to mount a permission to a role in a defensive manner.
     *
     * @param mixed  $auth
     * @param string $roleName
     * @param string $permissionName
     * @return void
     */
    protected function mountPermissionToRole($auth, string $roleName, string $permissionName): void
    {
        try {
            $role = method_exists($auth, 'getRole') ? $auth->getRole($roleName) : null;
            if ($role === null) {
                $this->stdout("    > Role '{$roleName}' not found; skipping assignment of {$permissionName}.\n", Console::FG_YELLOW);
                return;
            }

            $roleItem = new AuthItem($role);

            // Check if the permission is already a child
            $children = method_exists($roleItem, 'getChildren') ? $roleItem->getChildren() : [];
            if (in_array($permissionName, $children, true)) {
                $this->stdout("    > Permission '{$permissionName}' already assigned to role '{$roleName}'; skipping.\n", Console::FG_GREEN);
                return;
            }

            if (method_exists($roleItem, 'addChildren')) {
                $roleItem->addChildren([$permissionName]);
            } else {
                // fallback: try auth manager addChild if available
                if (method_exists($auth, 'getPermission') && method_exists($auth, 'addChild')) {
                    $perm = $auth->getPermission($permissionName);
                    if ($perm !== null) {
                        $auth->addChild($role, $perm);
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->stdout("    > Error assigning permission {$permissionName} to role {$roleName}: " . $e->getMessage() . "\n", Console::FG_RED);
        }
    }


    /**
     * Tiny compatibility helper for PHP < 8's str_contains behavior.
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    protected function contains(string $haystack, string $needle): bool
    {
        if (function_exists('str_contains')) {
            return str_contains($haystack, $needle);
        }
        return strpos($haystack, $needle) !== false;
    }
}
