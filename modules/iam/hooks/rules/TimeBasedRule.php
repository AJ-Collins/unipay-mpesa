<?php

namespace iam\hooks\rules;

use Yii;

/**
 * Checks if the current time is before 16:00 (4:00 PM)
 */
class TimeBasedRule extends \helpers\auth\Rule
{
    public $description = 'Only allow action before 16:00 (4:00 PM)';

    public function execute($user, $item, $params)
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        $currentHour = (int)date('H');
        $currentMinute = (int)date('i');
        $currentTimeInMinutes = ($currentHour * 60) + $currentMinute;
        
        // 16:00 = 960 minutes from midnight
        return $currentTimeInMinutes < 960;
    }
}
