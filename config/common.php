<?php
require_once __DIR__ . '/wrapper.php';
$wrapper = new ConfigWrapper();
return [
    //'id' => $_SERVER['APP_CODE'],
    'name' => $_SERVER['APP_NAME'],
    'basePath' => dirname(__DIR__),
    'timeZone' => 'Africa/Nairobi',
    'aliases' =>  $wrapper->load('aliases'),
    'modules' => $wrapper->load('modules'),
    'runtimePath' => dirname(__DIR__) . '/storage/runtime',
    'bootstrap' => [
        'log',
        'queue',
        function () {
            try {
                $queueConfig = (new ConfigWrapper())->messageBroker(false);

                // Only act if using DB-based queue
                if ($queueConfig['class'] !== \yii\queue\db\Queue::class) {
                    Yii::info('Queue bootstrap skipped (not using DB queue).', __METHOD__);
                    return;
                }

                // Check if the queue table exists
                if (Yii::$app->db->schema->getTableSchema('{{%queue}}', true) === null) {
                    Yii::info('Queue table not found — creating automatically...', __METHOD__);
                    (new \helpers\migrations\QueueMigration())->safeUp();
                    Yii::info('Queue table created successfully.', __METHOD__);
                }
            } catch (\Throwable $e) {
                Yii::warning('Queue bootstrap failed: ' . $e->getMessage(), __METHOD__);
            }
        }
    ],
    'params' => $wrapper->load('params'),
    'components' => [
        'queue' => $wrapper->messageBroker(),
        'settings' => [
            'class' => 'helpers\settings\SystemSettings',
            'cacheDuration' => 3600,
        ],
        'cache' => $wrapper->cache(),
        'authManager' => [
            'class' => \helpers\auth\AuthManager::class,
            'cache' => 'cache',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => $wrapper->logger(),
        ],
        'db' => $wrapper->dbDriver('CORE_DB'),
    ],
    'params' => $wrapper->load('params'),
];
