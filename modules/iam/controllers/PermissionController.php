<?php

namespace iam\controllers;


class PermissionController extends \iam\hooks\AccessController
{
    public $permissions = [
        'iamPermissionEdit' => 'Update System Permissions',
    ]; 
    public function getType()
    {
        return \helpers\auth\Item::TYPE_PERMISSION;
    }
}
