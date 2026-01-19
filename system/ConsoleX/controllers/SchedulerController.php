<?php

namespace ConsoleX\controllers;

use Yii;
use Cron\CronExpression;
use helpers\cron\SchedulerModel;
use ConsoleX\hooks\BaseController as Controller;

class SchedulerController extends Controller
{
    /** @var bool Stop the loop on SIGTERM */
    private $stop = false;

    public function init()
    {
        parent::init();
        // Handle termination signals
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
        }
    }

    public function actionRun()
    {
        $sleepMicroseconds = 200_000; // 0.2s loop interval
        $batchSize = 50;
        while (!$this->stop) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            // Fetch due tasks not locked
            $tasks = SchedulerModel::find()
                ->where(['<=', 'next_run_at', time()])
                ->andWhere(['is_deleted' => false, 'status' => 10])
                ->andWhere(['locked_at' => null])
                ->orderBy('next_run_at ASC')
                ->limit($batchSize)
                ->all();
            foreach ($tasks as $task) {
                // Reload task to ensure atomic claim
                $taskReloaded = SchedulerModel::find()->where(['task_id' => $task->task_id, 'locked_at' => null, 'is_deleted' => false])->one();
                if (!$taskReloaded) {
                    continue; // already claimed
                }
                $taskReloaded->updateAttributes(['locked_at' => time()]);
                // Claim successful, proceed to run
                $serviceClass = $taskReloaded->system_service;
                if (!class_exists($serviceClass)) {
                    Yii::error("Scheduler: Class not found {$serviceClass}", __METHOD__);
                    $taskReloaded->updateAttributes([
                        'locked_at' => time(),
                        'status' => $taskReloaded->statusCode->failed,
                    ]);
                    continue;
                }
                try {
                    // Safe payload
                    $payload = is_array($taskReloaded->service_payload) ? $taskReloaded->service_payload : [];
                    Yii::$app->queue->push(new $serviceClass($payload));
                    $taskReloaded->status = $taskReloaded->statusCode->active;
                    $nextRun = $this->calculateNextRun($taskReloaded);
                    // One-time tasks -> mark deleted
                    if ($nextRun === false) {
                        $taskReloaded->status = $taskReloaded->statusCode->completed;
                        $nextRun = $taskReloaded->next_run_at;
                    }
                    $taskReloaded->updateAttributes([
                        'last_run_at' => time(),
                        'next_run_at' =>  $nextRun,
                        'locked_at' => null,
                        'status' => $taskReloaded->status,
                    ]);
                } catch (\Throwable $e) {
                    Yii::error("Scheduler: Error queuing job for class {$serviceClass}: " . $e->getMessage(), __METHOD__);
                    $taskReloaded->updateAttributes([
                        'locked_at' => time(),
                        'status' => $taskReloaded->statusCode->failed,
                    ]);
                    continue;
                }
            }
            usleep($sleepMicroseconds);
        }
        Yii::info("Scheduler stopped gracefully", __METHOD__);
    }

    /**
     * Calculate next run timestamp
     */
    private function calculateNextRun(SchedulerModel $task)
    {
        if ($task->schedule_type === 'cron' && $task->schedule_value) {
            try {
                $cron = new CronExpression($task->schedule_value);
                return $cron->getNextRunDate()->getTimestamp();
            } catch (\Exception $e) {
                Yii::error("Invalid cron expression: {$task->schedule_value}", __METHOD__);
                return false;
            }
        }
        if ($task->schedule_type === 'interval' && $task->schedule_value && $task->is_recurring) {
            return $task->next_run_at + (int)$task->schedule_value;
        }
        return false; // one-time task
    }
    /**
     * Handle SIGTERM / SIGINT
     */
    public function handleSignal($signal)
    {
        switch ($signal) {
            case SIGTERM:
            case SIGINT:
                Yii::info("Scheduler received termination signal", __METHOD__);
                $this->stop = true;
                break;
        }
    }
}
