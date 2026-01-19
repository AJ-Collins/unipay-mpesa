<?php
namespace helpers\auth;

use helpers\ActiveRecord;


class LoginAttempt extends ActiveRecord
{
    public static function tableName()
    {
        return '{{login_attempt}}';
    }

    public function rules()
    {
        return [
            [['ip_address'], 'required'],
            [['user_id'], 'integer'],
            [['username'], 'string', 'max' => 255],
            [['ip_address'], 'string', 'max' => 45],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'username' => 'Username',
            'ip_address' => 'IP Address',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(\Yii::$app->getUser()->identityClass, ['user_id' => 'user_id']);
    }
}