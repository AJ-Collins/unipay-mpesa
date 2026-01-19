<?php

namespace helpers\audit;

use Yii;
use donatj\UserAgent\UserAgentParser;

class AuditModel extends \yii\db\ActiveRecord
{
    public function fields()
    {
        return [
            'user' => function ($model) {
                return $model->user_id == 'NO_USER_ID' ? 'System' : \Yii::$app->user->identityClass::findOne($model->user_id)->profile->full_name;
            },
            'ip_info' => function ($model) {
                return (new \helpers\IPinfo())->getInfo($model->ip_address);
            },
            'user_agent' => function ($model) {
                return $model->getUserAgent();
            },
            'request_context' => function ($model) {
                return [
                    'request_route' => $model->request_route,
                    'headers' => json_decode($model->headers, true),
                    'query_params' => json_decode($model->query_params, true),
                    'body_params' => json_decode($model->body_params, true),
                    'raw_body' => $model->raw_body,
                    'url' => $model->url,
                    'request_method' => $model->request_method,
                ];
            },
            'field_name',
            'old_value',
            'new_value',
            'audit_time' => function ($model) {
                return \Yii::$app->formatter->asDatetime($model->audit_time, 'yyyy-MM-dd HH:mm:ss');
            },
            'operation',
            'process_time' => function ($model) {
                $seconds = $model->duration;
                $precision = 3;
                if ($seconds >= 1) {
                    return Yii::$app->formatter->asDuration($seconds);
                }
                if ($seconds >= 0.001) {
                    return number_format($seconds * 1000, $precision) . ' ms';
                }
                return number_format($seconds * 1000000, $precision) . ' µs';
            },
            'memory_used' => function ($model) {
                return Yii::$app->formatter->asShortSize($model->memory_max);
            },
        ];
    }

    public static function tableName()
    {
        return '{{%audit_trail}}';
    }
    public function getUser()
    {
        return $this->hasOne((Yii::$app->getUser()->identityClass), ['user_id' => 'user_id']);
    }
    public function getUserAgent()
    {
        $userAgentString = $this->user_agent;
        $parser = new UserAgentParser();
        $agent = $parser->parse($userAgentString);
        return [
            'platform' => $agent->platform(),
            'browser' => $agent->browser(),
            'browser_version' => $agent->browserVersion(),
        ];
    }
}
