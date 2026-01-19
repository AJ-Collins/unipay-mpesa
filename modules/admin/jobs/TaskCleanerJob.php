<?php

namespace admin\jobs;

use helpers\BaseJob;
use helpers\cron\SchedulerModel;

class TaskCleanerJob extends BaseJob
{
    public $payload;
    public $schedule_type;
    public $task_age = 0;
    public $task_status;

    public function init()
    {
        parent::init();
        $model = new SchedulerModel();
        $this->payload = [
            [
                'key' => 'schedule_type',
                'label' => 'Schedule Type',
                'type' => 'dropDownList',
                'predefinedOptions' =>  [
                    'cron' => 'Cron Expression',
                    'interval' => 'Interval (seconds)',
                    'all' => 'All Types',
                ],
                'validations' => [
                    [['schedule_type'], 'required', 'message' => 'Schedule type is required'],
                    [['schedule_type'], 'in', 'range' => ['cron', 'interval', 'all'], 'message' => 'Invalid schedule type selected'],
                ]
            ],
            [
                'key' => 'task_age',
                'label' => 'Task Age (days)',
                'validations' => [
                    [['task_age'], 'required', 'message' => ' Minimum age in days for deletion is required'],
                    [['task_age'], 'integer', 'min' => 0, 'message' => 'Task age must be a non-negative integer'],
                ]
            ],
            [
                'key' => 'task_status',
                'type' => 'dropDownList',
                'label' => 'Status',
                'predefinedOptions' => [
                    $model->statusCode->completed => 'Completed',
                    $model->statusCode->inactive => 'Inactive',
                    $model->statusCode->failed => 'Failed',
                    $model->statusCode->true => 'Deleted',
                ],
                'validations' => [
                    [['task_status'], 'required', 'message' => 'Select a task status to continue'],
                    [['task_status'], 'integer', 'message' => 'Invalid status selected'],
                ]
            ],
        ];
    }
    /**
     * Execute the task cleaner job to remove old scheduler tasks
     * 
     * This method deletes scheduler tasks based on:
     * - Task age (calculated from last_run_at timestamp)
     * - Task status (completed, inactive, or deleted)
     * - Schedule type (cron, interval, or all)
     * - Only unlocked tasks are considered for deletion
     * 
     * @param mixed $queue The queue instance executing this job
     * @return void
     */
    public function execute($queue)
    {
        // Calculate cutoff timestamp (tasks older than this are candidates)
        $cutoff = time() - ($this->task_age * 24 * 60 * 60);

        // Base conditions: unlocked tasks
        $conditions = [
            'and',
            ['locked_at' => null],
        ];

        // Handle never-run tasks (last_run_at IS NULL) as "very old"
        $ageCondition = [
            'or',
            ['is', 'last_run_at', new \yii\db\Expression('NULL')],
            ['<=', 'last_run_at', $cutoff],
        ];
        $conditions[] = $ageCondition;

        // Filter by deletion status
        if ((int)$this->task_status === 1) {
            // Explicitly target soft-deleted tasks
            $conditions[] = ['is_deleted' => true];
        } else {
            $conditions[] = ['is_deleted' => false];
            $conditions[] = ['status' => (int)$this->task_status];
        }

        // Filter by schedule type
        if ($this->schedule_type !== 'all') {
            $conditions[] = ['schedule_type' => $this->schedule_type];
        }

        // Execute bulk delete
        $deletedCount = SchedulerModel::deleteAll($conditions);

        \Yii::info("TaskCleanerJob deleted {$deletedCount} old tasks (age ≥ {$this->task_age} days, status={$this->task_status}, type={$this->schedule_type})", __METHOD__);
    }
}
