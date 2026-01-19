<?php

namespace helpers\log;

use helpers\Html;
use helpers\ActiveRecord;
use Yii;

class LogModel extends ActiveRecord
{
    public function fields()
    {
        return [
            'level' => function ($model) {
                return $model->levelName;
            },
            'category',
            'message'=> function ($model) {
                return $model->safeMessage;
            },
            'log_time' => function ($model) {
                return Yii::$app->settings->DateTime($model->log_time);
            },
            'is_resolved',
        ];
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return (new DbTarget())->logTable;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['level', 'category', 'log_time', 'message'], 'required'],
            [['level'], 'integer'],
            [['log_time'], 'number'],
            [['message'], 'string'],
            [['category', 'prefix'], 'string', 'max' => 255],
        ];
    }

    /**
     * Get human-readable level name
     * @return string
     */
    public function getLevelName()
    {
        $levels = [
            1 => 'danger',
            2 => 'warning',
            4 => 'info',
        ];
        return $levels[$this->level] ?? 'unknown';
    }
    /**
     * Get XSS-safe message
     * @return string
     */
    public function getSafeMessage()
    {
        return Html::encode($this->message);
    }

    /**
     * Get error type with user information
     * @return string
     */
    public function getErrorType()
    {
        if (empty($this->prefix)) {
            return 'System Error';
        }

        if (!preg_match('/\[([^\]]*)\]\[([^\]]*)\]\[([^\]]*)\]/', $this->prefix, $matches)) {
            return 'System Error';
        }

        $ip = $matches[1] ?? '';
        $userId = $matches[2] ?? '';

        // Validate userId is numeric
        if (!preg_match('/^\d+$/', $userId) || (int)$userId <= 0) {
            return 'System Error';
        }

        // Get identity class safely
        $user = Yii::$app->user;
        if (!$user || !$user->identityClass) {
            return 'System Error';
        }

        $identityClass = $user->identityClass;

        // Safe query using parameterized values
        $userModel = $identityClass::findOne(['user_id' => (int)$userId]);

        if (!$userModel || !isset($userModel->profile->full_name)) {
            return 'System Error';
        }

        // Encode all output to prevent XSS
        return sprintf(
            'This error originated from: %s by: %s - %d',
            Html::encode($ip),
            Html::encode($userModel->profile->full_name),
            (int)$userId
        );
    }
}
