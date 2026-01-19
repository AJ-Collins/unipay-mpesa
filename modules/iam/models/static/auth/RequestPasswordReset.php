<?php

namespace iam\models\static\auth;

use Yii;
use yii\base\Model;
use iam\models\User;
use pulse\jobs\MailerJob;

class RequestPasswordReset extends Model
{
    public $username; // or username, depending on your login field
    private $_user;
    public function rules()
    {
        return [
            ['username', 'trim'],
            ['username', 'required'],
            ['username', 'validateUsername']
        ];
    }
    public function validateUsername($attribute, $params)
    {
        $user = User::find()
            ->joinWith('profile') // relation must exist
            ->where(['username' => $this->$attribute])
            ->orWhere(['email_address' => $this->$attribute])
            ->andWhere([
                'status' => User::STATUS_ACTIVE,
                'is_deleted' => 0,
            ])
            ->one();

        if ($user === null) {
            $this->addError(
                $attribute,
                'There is no user with the provided username.'
            );
            return;
        }

        // Optional: store user for reuse (login/reset flows)
        $this->_user = $user;
    }

    public function resetToken(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        $user = $this->getUser();
        if (!$user) {
            return false;
        }
        // Generate token (you already have this method)
        $user->generatePasswordResetToken();
        if (!$user->save(false)) {
            return false;
        }
        return $this->queueMail();
    }
    protected function queueMail(): bool
    {
        /* @var $user User */
        $user = $this->getUser();

        if (!$user) {
            return false;
        }
        Yii::$app->queue->push(new MailerJob([
            'to' => $user->profile->email_address,
            'subject' => 'Password Reset',
            'mailData' => $user,
            'template' => 'password-reset',
        ]));
        return true;
    }
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::find()
                ->joinWith('profile') // assuming relation exists
                ->where(['username' => $this->username])
                ->orWhere(['email_address' => $this->username])
                ->andWhere(['is_deleted' => 0])
                ->one();
        }
        return $this->_user;
    }
}
