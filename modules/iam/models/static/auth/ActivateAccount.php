<?php

namespace iam\models\static\auth;

use yii\base\Model;
use iam\models\User;

/**
 * Model for activating account via email verification token (GET request)
 */
class ActivateAccount extends Model
{
    public $token;

    private ?User $_user = null;

    public function rules()
    {
        return [
            ['token', 'required'],
            ['token', 'string', 'min' => 10],
            ['token', 'validateToken'],
        ];
    }

    /**
     * Validate token and check if user exists + not already active
     */
    public function validateToken($attribute, $params)
    {
        if ($this->hasErrors()) {
            return;
        }

        $user = $this->getUser();

        if (!$user) {
            $this->addError($attribute, 'Invalid or expired activation link.');
            return;
        }

        if (!User::isVerificationTokenValid($user->verification_token)) {
            $this->addError($attribute, 'The activation link has expired.');
            return;
        }
    }

    /**
     * Activate the user account
     * @return bool
     */
    public function activate(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $user = $this->getUser();
        if (!$user || $user->status === User::STATUS_ACTIVE) {
            return false;
        }

        // Activate account
        $user->status = User::STATUS_ACTIVE;
        $user->email_verified_at = time(); 
        $user->removeVerificationToken(); 

        return $user->save(false);
    }

    /**
     * Find user by verification token
     */
    public function getUser(): ?User
    {
        if ($this->_user === null) {
            $this->_user = User::findByVerificationToken($this->token);
        }
        return $this->_user;
    }

    /**
     * Get error message
     */
    public function getErrorMessage(): string
    {
        $errors = $this->getFirstErrors();
        return reset($errors) ?: 'Unable to activate your account.';
    }
}
