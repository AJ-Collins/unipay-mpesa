<?php
namespace helpers\cron;

use yii\base\BaseObject;

class TaskManager extends BaseObject
{
    public static function scheduleInterval($jobClass, $payload = [], $seconds = 60, $recurring = true)
    {
        $model = new SchedulerModel();
        $model->job_class = $jobClass;
        $model->payload = json_encode($payload);
        $model->interval_seconds = $seconds;
        $model->is_recurring = $recurring ? 1 : 0;
        $model->next_run_at = time() + $seconds;
        $model->save();
        return $model;
    }
    public static function scheduleCron($jobClass, $payload = [], $cronExpr = '* * * * *')
    {
        $model = new SchedulerModel();
        $model->job_class = $jobClass;
        $model->payload = json_encode($payload);
        $model->cron_expression = $cronExpr;
        $model->is_recurring = 1;
        $model->next_run_at = time();
        $model->save();
        return $model;
    }
    public static function scheduleOnce($jobClass, $payload = [], $delaySeconds = 0)
    {
        $model = new SchedulerModel();
        $model->job_class = $jobClass;
        $model->payload = json_encode($payload);
        $model->interval_seconds = $delaySeconds;
        $model->is_recurring = 0;
        $model->next_run_at = time() + $delaySeconds;
        $model->save();
        return $model;
    }
}
