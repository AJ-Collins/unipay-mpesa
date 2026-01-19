<?php

namespace iam\models;

use Yii;

class PasswordHistory extends \iam\hooks\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%password_history}}';
    }
    public function rules()
    {
        return [
            [['user_id', 'old_password'], 'required'],
            [['user_id'], 'integer'],
            [['old_password'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'user_id']],
        ];
    }
}
