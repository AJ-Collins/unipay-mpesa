<?php

namespace iam\models\static\rbac;

use Yii;
use yii\rbac\Rule;
use yii\base\Model;
use yii\helpers\Json;
use helpers\auth\Item;
use iam\hooks\AuthHelper;
use iam\hooks\AuthConfigs;
use yii\helpers\Inflector;
use yii\helpers\ArrayHelper;

/**
 * OpenAPI schema for Permissions
 * @OA\Schema(
 *  schema="Permissions",
 *  @OA\Property(property="permission_name", type="string",title="Permission Name", example="Manage Access Control Lists"),
 *  @OA\Property(property="ruleName", type="string",title="Rule Name", example="is_admin"),
 *  @OA\Property(property="description", type="string",title="Description", example="Access Control Permission"),
 * )
 */

/**
 * OpenAPI schema for Roles
 * @OA\Schema(
 *  schema="Roles",
 *  @OA\Property(property="role_name", type="string",title="Role Name", example="user"),
 *  @OA\Property(property="ruleName", type="string",title="Rule Name", example="is_admin"),
 *  @OA\Property(property="description", type="string",title="Description", example="General User Role"),
 * )
 */

/**
 * OpenAPI schema for Groups
 * @OA\Schema(
 *  schema="Groups",
 *  @OA\Property(property="group_name", type="string",title="Group Name", example="admin"),
 *  @OA\Property(property="ruleName", type="string",title="Rule Name", example="is_admin"),
 *  @OA\Property(property="description", type="string",title="Description", example="Administrator Group"),
 * )
 */

/**
 * AuthItem model represents an RBAC item (Role, Permission, or Group)
 * Used for managing authorization items in the system
 */
class AuthItem extends Model
{
    /** Scenario constant for update operations */
    const SCENARIO_UPDATE = 'update';

    /** @var string Item name/ID */
    public $name;

    /** @var int Item type (role, permission, or group) */
    public $type;

    /** @var string Human-readable description */
    public $description;

    /** @var string|null Name of the rule associated with this item */
    public $ruleName;

    /** @var string|null Additional data in JSON format */
    public $data;

    /** @var bool Whether this item is protected from deletion */
    public $protected = true;

    /**
     * @var Item The underlying RBAC item object
     */
    private $_item;

    /**
     * Define fields for API responses
     * @return array Field definitions
     */
    public function fields()
    {
        return [
            // Dynamic field name based on item type (role_name, permission_name, etc.)
            $this->label() . '_name' => function ($model) {
                return $model->description;
            },
            'ruleName'=>function($model) {
                return Inflector::camel2id($model->ruleName, '_');
            },
            // Description field returns data or empty string
            'description' => function ($model) {
                return is_null($model->data) ? '' : $model->data;
            },
        ];
    }

    /**
     * Constructor - initializes model from an RBAC item
     * @param Item|null $item The RBAC item to wrap
     * @param array $config Configuration array
     */
    public function __construct($item = null, $config = [])
    {
        $this->_item = $item;
        if ($item !== null) {
            // Populate model attributes from item
            $this->name = $item->name;
            $this->type = $item->type;
            $this->description = $item->description;
            $this->ruleName = $item->ruleName;
            $this->data = $item->data === null ? null : Json::encode($item->data);
        }
        parent::__construct($config);
    }

    /**
     * Get human-readable label based on item type
     * @return string 'role', 'permission', or 'group'
     */
    public function label()
    {
        if ($this->type === Item::TYPE_ROLE) {
            return 'role';
        } elseif ($this->type === Item::TYPE_PERMISSION) {
            return 'permission';
        } else {
            return 'group';
        }
    }

    /**
     * Validation rules
     * @return array Validation rules
     */
    public function rules()
    {
        return [
            [['ruleName'], 'validateRuleName'],
            // Check uniqueness only for new records or when name changes
            [['name'], 'checkUnique', 'when' => function () {
                return $this->isNewRecord || ($this->_item->name != $this->name);
            }],
            // Required fields with custom messages
            [['name'], 'required', 'message' => ucfirst($this->label()) . ' ID cannot be blank.'],
            [['description'], 'required', 'message' => ucfirst($this->label()) . ' name cannot be blank.'],
            [['type'], 'integer'],
            [['description', 'data', 'ruleName'], 'default'],
            [['name'], 'string', 'max' => 64],
            [['protected'], 'safe'],
            // Only allow alphanumeric, underscores, and dashes
            [['name'], 'match', 'pattern' => '/^[a-zA-Z0-9_\-]+$/', 'message' => 'Only alphanumeric characters, underscores and dashes are allowed.'],
        ];
    }
    public function validateRuleName($attribute, $params)
    {
        if (!empty($this->ruleName)) {
            $authManager = AuthConfigs::authManager();
            $rule = $authManager->getRule($this->ruleName);
            if ($rule === null) {
                $this->addError($attribute, 'The specified rule does not exist.');
            }
        }
    }
    /**
     * Post-validation processing to rename error fields
     */
    public function afterValidate()
    {
        parent::afterValidate();
        // Rename 'name' errors to dynamic field name (role_id, permission_id, etc.)
        if ($this->hasErrors('name')) {
            $this->addError($this->label() . '_id', $this->getFirstError('name'));
            $this->clearErrors('name');
        }
        // Rename 'description' errors to dynamic field name
        if ($this->hasErrors('description')) {
            $this->addError($this->label() . '_name', $this->getFirstError('description'));
            $this->clearErrors('description');
        }
    }

    /**
     * Validate that the item name is unique across all RBAC items
     */
    public function checkUnique()
    {
        $authManager = AuthConfigs::authManager();
        $value = $this->name;
        // Check if name already exists as role or permission
        if ($authManager->getRole($value) !== null || $authManager->getPermission($value) !== null) {
            $this->addError('name', ucfirst($this->label()) . ' ID already exists.');
        }
    }


    /**
     * Check if this is a new record (not yet saved)
     * @return bool True if new record
     */
    public function getIsNewRecord()
    {
        return $this->_item === null;
    }

    /**
     * Find an existing role by ID
     * @param string $id Role name/ID
     * @return null|self AuthItem instance or null if not found
     */
    public static function find($id)
    {
        $item = AuthConfigs::authManager()->getRole($id);
        if ($item !== null) {
            return new self($item);
        }
        return null;
    }

    /**
     * Save the item to the auth manager
     * @return bool|null True on success, null on validation failure
     */
    public function save()
    {
        if (!$this->validate()) {
            return null;
        }

        $manager = AuthConfigs::authManager();

        // Create new item if it doesn't exist
        if ($this->_item === null) {
            if ($this->type == Item::TYPE_ROLE) {
                $this->_item = $manager->createRole($this->name);
            } elseif ($this->type == Item::TYPE_PERMISSION) {
                $this->_item = $manager->createPermission($this->name);
            } elseif ($this->type == Item::TYPE_GROUP) {
                $this->_item = $manager->createGroup($this->name);
            }
            $isNew = true;
        } else {
            // Update existing item
            $isNew = false;
            $oldName = $this->_item->name;
        }

        // Update item properties
        $this->_item->name = $this->name;
        $this->_item->description = $this->description;
        $this->_item->ruleName = $this->ruleName;
        $this->_item->data = $this->data;

        // Add or update in auth manager
        if ($isNew) {
            $manager->add($this->_item);
        } else {
            $manager->update($oldName, $this->_item);
        }

        // Invalidate auth cache
        AuthHelper::invalidate();
        return true;
    }

    /**
     * Adds children (permissions/roles/groups) to this item
     * @param array $items List of child names (strings)
     * @return int Number of successfully added children
     */
    public function addChildren($items)
    {
        // Cannot add children if item doesn't exist
        if (!$this->_item) {
            return 0;
        }

        // Validate input
        if (!is_array($items) || empty($items)) {
            return 0;
        }

        $manager = AuthConfigs::authManager();
        $success = 0;

        foreach ($items as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $child = null;

            // Determine what type of child to look for based on parent type
            if ($this->_item->type == Item::TYPE_GROUP) {
                // Groups can have roles or groups as children
                $child = $manager->getRole($name);
                if ($child === null) {
                    $child = $manager->getGroup($name);
                }
            } elseif ($this->_item->type == Item::TYPE_ROLE) {
                // Roles can have permissions or roles as children
                $child = $manager->getPermission($name);
                if ($child === null) {
                    $child = $manager->getRole($name);
                }
            } else {
                // Permissions typically cannot have children
                continue;
            }

            // Skip if child not found
            if ($child === null) {
                Yii::warning("Child item '{$name}' not found for assignment to '{$this->_item->name}'", __METHOD__);
                continue;
            }

            // Prevent self-assignment
            if ($this->_item->name === $child->name) {
                continue;
            }

            // Skip if already assigned
            if ($manager->hasChild($this->_item, $child)) {
                continue;
            }

            try {
                $manager->addChild($this->_item, $child);
                $success++;
            } catch (\Exception $e) {
                Yii::error("Failed to assign '{$name}' to '{$this->_item->name}': " . $e->getMessage(), __METHOD__);
            }
        }

        // Invalidate cache if any changes were made
        if ($success > 0) {
            AuthHelper::invalidate();
        }

        return $success;
    }

    /**
     * Removes children (permissions/roles/groups) from this item
     * @param array $items List of child names (strings) to revoke
     * @return int Number of successfully removed children
     */
    public function removeChildren($items)
    {
        // Cannot remove children if item doesn't exist
        if (!$this->_item) {
            return 0;
        }

        // Validate input
        if (!is_array($items) || empty($items)) {
            return 0;
        }

        $manager = AuthConfigs::authManager();
        $success = 0;

        foreach ($items as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $child = null;

            // Determine expected child type based on parent
            if ($this->_item->type == Item::TYPE_GROUP) {
                // Groups typically contain roles or other groups
                $child = $manager->getRole($name) ?? $manager->getGroup($name);
            } elseif ($this->_item->type == Item::TYPE_ROLE) {
                // Roles typically contain permissions or other roles
                $child = $manager->getPermission($name) ?? $manager->getRole($name);
            } else {
                // Permissions cannot have children
                continue;
            }

            // If child not found, log and skip
            if ($child === null) {
                Yii::warning("Child item '{$name}' not found when trying to revoke from '{$this->_item->name}'", __METHOD__);
                continue;
            }

            // Prevent self-removal
            if ($this->_item->name === $child->name) {
                continue;
            }

            // Skip if not actually assigned
            if (!$manager->hasChild($this->_item, $child)) {
                continue;
            }

            try {
                $manager->removeChild($this->_item, $child);
                $success++;
            } catch (\Exception $e) {
                Yii::error("Failed to revoke '{$name}' from '{$this->_item->name}': " . $e->getMessage(), __METHOD__);
            }
        }

        // Invalidate cache if any changes were made
        if ($success > 0) {
            AuthHelper::invalidate();
        }

        return $success;
    }

    /**
     * Returns available and assigned child items for this RBAC item
     * @return array [
     *     'available' => array of items that can be assigned,
     *     'assigned'  => array of currently assigned items
     * ]
     * Each item: ['type' => 'role|permission|group', 'display_name' => string]
     */
    public function getItems()
    {
        if (!$this->_item) {
            return ['available' => [], 'assigned' => []];
        }

        $manager = AuthConfigs::authManager();
        $available = [];
        $assigned  = [];

        // Determine what can be assigned based on parent type
        if ($this->_item->type == Item::TYPE_ROLE) {
            // Role can have roles and permissions as children
            $this->loadItemsInto($manager->getRoles(), $available);
            $this->loadItemsInto($manager->getPermissions(), $available);
        } else {
            // Group can have groups and roles as children
            $this->loadItemsInto($manager->getGroups(), $available);
            $this->loadItemsInto($manager->getRoles(), $available);
        }

        // Load currently assigned children
        foreach ($manager->getChildren($this->_item->name) as $childItem) {
            $assigned[$childItem->name] = [
                'type'         => strtolower($this->getTypeName($childItem->type)),
                'display_name' => $childItem->description ?? $childItem->name,
            ];
            // Remove from available list
            unset($available[$childItem->name]);
        }

        // Prevent self-assignment
        unset($available[$this->_item->name]);

        // Sort alphabetically by key (name)
        ksort($available);
        ksort($assigned);

        return [
            'available' => $available,
            'assigned'  => $assigned,
        ];
    }

    /**
     * Helper method to load items from a collection into target array
     * @param array $itemsCollection Collection of RBAC items
     * @param array $target Target array to populate (passed by reference)
     */
    private function loadItemsInto($itemsCollection, array &$target)
    {
        foreach ($itemsCollection as $name => $item) {
            $target[$name] = [
                'type'         => strtolower($this->getTypeName($item->type)),
                'display_name' => $item->description ?? $name,
            ];
        }
    }

    /**
     * Get the underlying RBAC item object
     * @return Item|null
     */
    public function getItem()
    {
        return $this->_item;
    }

    /**
     * Get human-readable type name for an item type constant
     * @param int|null $type Item type constant or null to get all types
     * @return string|array Type name or array of all type names
     */
    static function getTypeName($type = null)
    {
        $result = [
            Item::TYPE_PERMISSION => 'Permission',
            Item::TYPE_ROLE => 'Role',
            Item::TYPE_GROUP => 'Group',
        ];

        if ($type === null) {
            return $result;
        }

        return $result[$type];
    }

    /**
     * Scan application modules for permissions defined in controllers
     * Compares found permissions with existing ones in auth manager
     * @param array $list Initial list of permissions
     * @return array ['add' => permissions to add, 'remove' => permissions to remove]
     */
    public function scanPermissions($list = [])
    {
        $manager = AuthConfigs::authManager();
        // Get existing permissions from auth manager
        $exist = ArrayHelper::map($manager->getPermissions(), 'name', 'description');

        // Scan all module controllers for permissions
        foreach (new \DirectoryIterator(Yii::getAlias('@modules')) as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $dir = Yii::getAlias('@modules/') . $fileinfo->getFilename() . '/controllers';

                // Process each controller file
                foreach (glob("{$dir}/*.php") as $filename) {
                    // Convert file path to class name
                    $controller = str_replace('.php', '', str_replace('/', '\\', str_replace(Yii::getAlias('@modules'), "", $filename)));
                    $controller = new $controller(Yii::$app->id, true);

                    // Merge permissions if controller has them defined
                    if (property_exists($controller, 'permissions')) {
                        $list = array_merge($list, $controller->permissions);
                    }
                }
            }
        }

        // Return permissions to add and remove
        return [
            'add'    => array_diff_key($list, $exist),    // In scanned but not in DB
            'remove' => array_diff_key($exist, $list),    // In DB but not in scanned
        ];
    }
}
