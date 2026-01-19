<?php

namespace helpers;

use Yii;
use yii\base\BaseObject;

class BaseJob extends BaseObject implements \yii\queue\JobInterface
{
    

    public function execute($queue)
    {
        // Override this method in subclasses to implement job logic
    }
    public function getTtr()
    {
        return 30 * 60; // 30 minutes
    }
    public function canRetry($attempt, $error)
    {
        return ($attempt < 5) && ($error instanceof \Exception);
    }
}
