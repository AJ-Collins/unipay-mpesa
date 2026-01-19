<?php

namespace helpers\auth;

class AuthUser extends \yii\web\User
{
    public $identityClass = 'iam\models\User';
    public $enableAutoLogin = false;
    public $enableSession = false;
    private $_access = [];
    public function can($permissionName, $params = [], $silent_err = false, $allowCaching = true)
    {
        if ($allowCaching && empty($params) && isset($this->_access[$permissionName])) {
            return $this->_access[$permissionName];
        }
        if (($accessChecker = $this->getAccessChecker()) === null) {
            return false;
        }
        $access = $accessChecker->checkAccess($this->getId(), $permissionName, $params);
        if ($allowCaching && empty($params)) {
            $this->_access[$permissionName] = $access;
        }

        if ($silent_err) {
            return $access;
        } else {
            if (!$access) {
                throw new \yii\web\ForbiddenHttpException('You do not have permission to perform this action.');
            } else {
                return true;
            }
        }
    }
}
