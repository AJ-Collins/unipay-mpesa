<?php

namespace iam\models\static\auth;

use Yii;
use iam\models\User;

/**
 * @OA\Schema(
 *  schema="ChangePassword",
 *  @OA\Property(property="currentPassword", type="string", title="Current Password", example="@dmiN123"),
 *  @OA\Property(property="newPassword", type="string", title="New Password", example="@dmiN1234"),
 *  @OA\Property(property="confirmNewPassword", type="string", title="Confirm New Password", example="@dmiN1234"),
 * )
 */
class ChangePassword extends AuthModel
{
    public $currentPassword;
    public $newPassword;
    public $confirmNewPassword;

    private ?User $_user = null;

    public function rules()
    {
        return [
            [['currentPassword', 'newPassword', 'confirmNewPassword'], 'required'],
            // Current password validation
            ['currentPassword', 'validateCurrentPassword'],
            // New password strength
            ['newPassword', 'string', 'min' => 8, 'max' => 72],
            [
                'newPassword',
                'match',
                'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W).+$/',
                'message' => 'Password must be at least 8 characters and contain: uppercase, lowercase, number, and special character.'
            ],
            // Confirm password
            [
                'confirmNewPassword',
                'compare',
                'compareAttribute' => 'newPassword',
                'message' => "Passwords don't match"
            ],
            // Cannot be same as current
            [
                'newPassword',
                'compare',
                'compareAttribute' => 'currentPassword',
                'operator' => '!==',
                'message' => 'New password cannot be the same as your current password.'
            ],
        ];
    }
    /**
     * Validate current password + password history
     */
    public function validateCurrentPassword($attribute, $params)
    {
        if ($this->hasErrors()) {
            return;
        }
        $user = $this->getUser();
        if (!$user) {
            $this->addError($attribute, 'User not found.');
            return;
        }
        //  Check current password
        if (!$user->validatePassword($this->currentPassword)) {
            $this->addError($attribute, 'Current password is incorrect.');
            return;
        }
        // Check password history (last 10 passwords)
        if (!$user->validatePasswordHistory($this->newPassword)) {
            $this->addError('newPassword', 'Use of previously used password is not allowed.');
            return;
        }
    }
    /**
     * Change password + security best practices
     */
    public function change(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        $user = $this->getUser();
        // Change password + regenerate auth key
        $user->setPassword($this->newPassword);
        $user->generateAuthKey();
        $user->updatePasswordHistory($this->newPassword);
        //  BLACKLIST ALL CURRENT ACCESS TOKENS (THIS IS THE KEY!)
        $this->blacklistAllCurrentAccessTokens($user->user_id);
        //  Delete HttpOnly refresh cookie
        Yii::$app->response->cookies->remove('refresh_token');
        return $user->save(false);
    }

    /**
     * Get current logged-in user
     */
    public function getUser(): ?User
    {
        if ($this->_user === null) {
            $this->_user = Yii::$app->user->identity;
        }
        return $this->_user;
    }
}
