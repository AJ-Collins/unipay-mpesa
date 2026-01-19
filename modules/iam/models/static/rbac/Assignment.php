<?php

namespace iam\models\static\rbac;

use Yii;
use yii\base\BaseObject;
use iam\hooks\AuthHelper;
use iam\hooks\AuthConfigs;

class Assignment extends BaseObject
{
    public $id;
    public $user;
    public function __construct($id, $user = null, $config = array())
    {
        $this->id = $id;
        $this->user = $user;
        parent::__construct($config);
    }
    public function assign($name)
    {
        $manager = AuthConfigs::authManager();
        $success = 0;
        $item = $manager->getGroup($name);
        if($manager->assign($item, $this->id)) {
            $success++;
        }
        AuthHelper::invalidate();
        return $success;
    }
    public function revoke($name)
    {
        $manager = AuthConfigs::authManager();
        $success = 0;
        $item = $manager->getGroup($name);
        if($manager->revoke($item, $this->id)) {
            $success++;
        }
        AuthHelper::invalidate();
        return $success;
    }
    public function getItems()
    {
        $manager = AuthConfigs::authManager();
        $available = [];
        foreach (array_keys($manager->getRoles()) as $name) {
            $available[$name] = 'role';
        }

        foreach (array_keys($manager->getPermissions()) as $name) {
            if ($name[0] != '/') {
                $available[$name] = 'permission';
            }
        }

        $assigned = [];
        foreach ($manager->getAssignments($this->id) as $item) {
            $assigned[$item->roleName] = $available[$item->roleName];
            unset($available[$item->roleName]);
        }

        ksort($available);
        ksort($assigned);
        return [
            'available' => $available,
            'assigned' => $assigned,
        ];
    }
    public function __get($name)
    {
        if ($this->user) {
            return $this->user->$name;
        }
    }
}
