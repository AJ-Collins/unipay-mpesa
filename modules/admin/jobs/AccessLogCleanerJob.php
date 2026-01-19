<?php

namespace admin\jobs;

use helpers\BaseJob;
use helpers\cron\SchedulerModel;

/**
 * AccessLogCleanerJob
 * 
 * Background job that cleans up old access log entries from the system.
 * This job is typically scheduled to run periodically to maintain database
 * performance and comply with data retention policies.
 */
class AccessLogCleanerJob extends BaseJob
{
    /**
     * @var array Job configuration payload containing custom parameters
     */
    public $payload;
    
    /**
     * @var int Number of days after which access logs should be deleted (default: 0)
     */
    public $access_log_age = 0;

    /**
     * Initialize the job with configuration payload
     * 
     * Sets up the job parameters including:
     * - access_log_age: Minimum age in days for log deletion
     * 
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->payload = [
            [
                'key' => 'access_log_age',
                'label' => 'Access Log Age (days)',
                'validations' => [
                    [['access_log_age'], 'required', 'message' => ' Minimum age in days for deletion is required'],
                    [['access_log_age'], 'integer', 'min' => 0, 'message' => 'Access log age must be a non-negative integer'],
                ]
            ]
        ];
    }
    
    /**
     * Execute the access log cleaner job
     * 
     * Deletes access log entries older than the specified age (access_log_age).
     * The deletion is performed based on the created_at timestamp compared
     * against a calculated cutoff time.
     * 
     * @param mixed $queue The queue instance executing this job
     * @return void
     */
    public function execute($queue)
    {
        // Calculate cutoff timestamp: current time minus (days * seconds_per_day)
        $cutoff = time() - ($this->access_log_age * 24 * 60 * 60);

        // Build deletion conditions: only records created before the cutoff time
        $conditions = [
            'and',
            ['<=', 'created_at', $cutoff],
        ];
        
        // Execute bulk deletion of old access log records
        SchedulerModel::deleteAll($conditions);
    }
}
