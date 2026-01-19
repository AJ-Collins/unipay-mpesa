<?php

namespace iam\models\static\auth;

use Yii;
use iam\models\User;

/**
 *@OA\Schema(
 *  schema="Me",
 *  @OA\Property(property="username", type="string", title="Username", example="admin", description="User's username."),
 *  @OA\Property(property="last_login_at", type="string", format="date-time", title="Last Login", example="2023-01-15T10:30:00+00:00", description="Last login timestamp in ISO 8601 format."),
 *  @OA\Property(property="last_login_ip", type="string", title="Last Login IP", example="192.168.1.1", description="Last login IP address."),
 *  @OA\Property(property="email_verified", type="boolean", title="Email Verified", example=true, description="Whether email is verified."),
 *  @OA\Property(property="mobile_verified", type="boolean", title="Mobile Verified", example=true, description="Whether mobile is verified."),
 *  @OA\Property(property="is_locked", type="boolean", title="Is Locked", example=false, description="Whether account is locked."),
 *  @OA\Property(property="permissions", type="array", @OA\Items(type="string"), title="Permissions", example={"read", "write"}, description="Array of user permission keys."),
 *  @OA\Property(property="profile", type="object", title="Profile", description="User profile data.",ref="#/components/schemas/Profile"),
 * )
 */
class Me extends User
{
    public function fields()
    {
        return [
            'username',
            'last_login_at'  => function ($model) {
                return $model->last_login_at ? date('c', $model->last_login_at) : null;
            },
            'last_login_ip' => function ($model) {
                return $model->last_login_ip;
            },
            'email_verified' => function ($model) {
                return (bool) $model->email_verified_at;
            },
            'mobile_verified' => function ($model) {
                return (bool) $model->mobile_verified_at;
            },
            'is_locked'      => function ($model) {
                return $model->is_locked;
            },
            'permissions'    => function ($model) {
                return array_keys(Yii::$app->authManager->getPermissionsByUser($model->user_id));
            },
            'profile'        => function ($model) {
                return $model->profile;
            },
        ];
    }
}
