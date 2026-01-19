<?php
namespace helpers\log;

use Yii;
use helpers\audit\AuditModel;
use donatj\UserAgent\UserAgentParser;

class AccessLogModel extends \helpers\ActiveRecord
{
    public function fields()
            {
                return [
                    'user' => function ($model) {
                        return $model->user_id == 'NO_USER_ID' ? 'System' : \Yii::$app->user->identityClass::findOne($model->user_id)->profile->full_name;
                    },
                    'action',
                    'description',
                    'extra_data',
                    'ip_info' => function ($model) {
                        return (new \helpers\IPinfo())->getInfo($model->ip_address);
                    },
                    'user_agent' => function ($model) {
                        return (new AuditModel(['user_agent' => $model->user_agent]))->getUserAgent();
                    },
                    'access_time' => function ($model) {
                        return \Yii::$app->settings->DateTime($model->created_at);
                    },
                ];
            }
    public static function tableName()
    {
        return '{{%access_log}}';
    }

    public function rules()
    {
        return [
            ['user_id', 'integer'],
            ['action', 'string', 'max' => 255],
            ['description', 'string'],
            ['ip_address', 'ip'],
            ['user_agent', 'string'],
            ['created_at', 'integer'],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(Yii::$app->getUser()->identityClass, ['user_id' => 'user_id']);
    }
    public function userAgent(){
        $userAgentString = $this->user_agent;
        $parser = new UserAgentParser();
        return $parser->parse($userAgentString);
    }


}
