<?php

namespace iam\models\static\auth;

use Yii;
use iam\models\User;

/**
 * @OA\Schema(
 *  schema="ResetPassword",
 *  @OA\Property(property="password", type="string", title="New Password", example="@dmiN1234", description="Password must be at least 12 characters and contain uppercase, lowercase, number, and special character."),
 *  @OA\Property(property="confirmPassword", type="string", title="Confirm New Password", example="@dmiN1234",  description="Must match the new password."),
 * )
 */
class ResetPassword extends AuthModel
{
    public $token;
    public $password;
    public $confirmPassword;

    private ?User $_user = null;

    public function rules()
    {
        return [
            [['password', 'confirmPassword'], 'required'],
            ['password', 'required', 'message' => 'Please choose a password you can remember'],
            ['password', 'string', 'min' => 8],
            ['password', 'match', 'pattern' => '/^\S*(?=\S*[\W])(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/', 'message' => 'Password should contain at least: 1 number, 1 lowercase letter, 1 uppercase letter, and 1 special character'],
            ['confirmPassword', 'compare', 'compareAttribute' => 'password', 'message' => "Passwords don't match"],
            ['confirmPassword', 'validateToken'],
        ];
    }
    /**
     * Validate the reset token
     */
    public function validateToken($attribute, $params)
    {
        if ($this->hasErrors()) {
            return;
        }
        if (empty($this->token) || !is_string($this->token)) {
            $this->addError($attribute, 'Password reset token cannot be blank.');
        }
        $user = $this->getUser();
        if (!$user) {
            $this->addError($attribute, 'Invalid or expired reset token.');
        } else {
            if (!$user->validatePasswordHistory($this->password)) {
                $this->addError($attribute, 'Use a password you have never used before.');
            }
        }
    }

    /**
     * Reset the password
     * @return bool
     */
    public function reset(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        $user = $this->getUser();
        if (!$user) {
            return false;
        }
        // Set new password
        $user->setPassword($this->password);
        // Regenerate auth key 
        $user->generateAuthKey();
        // Clear reset token
        $user->removePasswordResetToken();
        // Update password history
        $user->updatePasswordHistory($this->password);
        //  BLACKLIST ALL CURRENT ACCESS TOKENS 
        $this->blacklistAllCurrentAccessTokens($user->user_id);
        //  Delete HttpOnly refresh cookie
        Yii::$app->response->cookies->remove('refresh_token');
        return $user->save(false);
    }


    /**
     * Find user by valid reset token
     */
    public function getUser(): ?User
    {
        if ($this->_user === null) {
            $user = User::findByPasswordResetToken($this->token);
            $this->_user = ($user instanceof User) ? $user : null;
        }
        return $this->_user;
    }
}
