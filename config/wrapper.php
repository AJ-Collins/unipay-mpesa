<?php

class ConfigWrapper
{
    public $_aliases;
    public $_bootstrap;
    public $_modules;
    public $_params;
    public $_allowedDomains;
    public $_tokens;
    public function __construct()
    {
        $this->_tokens = [
            '{uid}' => '<uid:\\d[\\d,]*>',
            '{id}' => '<id:[a-zA-Z0-9\\-]+>',
            '{username}' => '<username:[a-zA-Z0-9\\-]+>',
            '{type}' => '<type:[a-zA-Z0-9\\-]+>',
        ];
        $this->_aliases = [
            '@ConsoleX' => '@app/system/ConsoleX',
            '@bower' => '@vendor/bower-asset',
            '@npm'   => '@vendor/npm-asset',
            '@helpers' => '@app/system/Components',
            '@OmniCraft' => '@app/system/OmniCraft',
            '@resources' => '@app/resources',
            '@modules' => '@app/modules',
            '@storage' => '@app/storage/vault',
        ];
        $this->_params = [
            'defaultPageSize' => (isset($_SERVER['PAGE_SIZE'])) ?  $_SERVER['PAGE_SIZE'] : 25,
            'pageSizeLimit' => (isset($_SERVER['PAGE_SIZE_LIMIT'])) ?  $_SERVER['PAGE_SIZE_LIMIT'] : 100,
            'allowedDomains' => (isset($_SERVER['APP_SAFE_DOMAINS'])) ? explode(',', $_SERVER['APP_SAFE_DOMAINS']) : ['*'],
            
            'CacheDuration' => ['System' => 86400],
        ];
    }
    public function load($item)
    {
        $wrapper = $routes = [];
        $wrapper['aliases'] = $this->_aliases;
        $wrapper['modules'] = $this->_modules;
        $wrapper['tokens'] = $this->_tokens;
        $wrapper['params'] = $this->_params;
        foreach (new DirectoryIterator(dirname(__DIR__) . '/modules') as $index => $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $wrapper['aliases']['@' . $fileinfo->getFilename()] = '@app/modules/' . $fileinfo->getFilename();
                $wrapper['migrationPaths'][] = '@' . $fileinfo->getFilename() . '/migrations';
                if ($fileinfo->getFilename() !== 'website') {
                    $wrapper['modules'][$fileinfo->getFilename()] = [
                        'class' => $fileinfo->getFilename() . '\\Module'
                    ];
                    if ($fileinfo->getFilename() !== 'website') {
                        $wrapper['controllers'][] = $fileinfo->getFilename();
                        $dir = dirname(__DIR__) . "/modules/" . $fileinfo->getFilename() . "/routers";
                        foreach (glob("{$dir}/*.php") as $filename) {
                            $route = require($filename);
                            $routes = array_merge($routes, $route);
                        }
                        $wrapper['routes'] = $routes;
                    }
                }
            }
        }

        return $wrapper[$item];
    }
    public function dbDriver($selector = null, $id = false)
    {
        if (isset($_SERVER[$selector . '_USERNAME'])) {
            $connection = [
                'class' => '\yii\db\Connection',
                'username' => $_SERVER[$selector . '_USERNAME'],
                'password' => $_SERVER[$selector . '_PASSWORD'],
                'charset' => 'utf8',
                'enableSchemaCache' => false,
                'schemaCacheDuration' =>  isset($_SERVER['CACHE_DURATION']) ? $_SERVER['CACHE_DURATION'] : 1800,
                'schemaCache' => 'cache',
            ];
        }
        switch ($_SERVER[$selector . '_DRIVER']) {
            case "mssql":
                $connection = array_merge($connection, [
                    'driverName' => 'sqlsrv',
                    'dsn' => "sqlsrv:Server={$_SERVER[$selector . '_HOST']};Database={$_SERVER[$selector . '_DATABASE']}",
                ]);
                break;
            case "pgsql":
                $connection = array_merge($connection, [
                    'dsn' => "pgsql:host={$_SERVER[$selector . '_HOST']};port=" . (isset($_SERVER[$selector . '_PORT']) ? $_SERVER[$selector . '_PORT'] : '5432') . ";dbname={$_SERVER[$selector . '_DATABASE']}",
                    'schemaMap' => [
                        'pgsql' => [
                            'class' => 'yii\db\pgsql\Schema',
                            'defaultSchema' => !$id ? 'public' : $id //specify your schema here
                        ]
                    ],
                ]);
                break;
            case "mongodb":
                $connection = $this->mongodb($selector, $id);
                break;
            case "mysql":
                $port = $_SERVER[$selector . '_PORT'] ?? '3306';
                $connection['dsn'] = "mysql:host={$_SERVER[$selector . '_HOST']};port={$port};dbname={$_SERVER[$selector . '_DATABASE']}";
                break;
            default:
                null;
        }
        return $connection;
    }
    protected function mongodb($selector, $id)
    {
        return [
            //'class' => \yii\mongodb\Connection::class,
            'dsn' => function () use ($selector, $id) {
                $id = !$id ? 'admin' : $id;
                $id = '?authSource=' . $id;
                $port = isset($_SERVER[$selector . '_PORT']) ? $_SERVER[$selector . '_PORT'] : '27017';
                return "mongodb://{$_SERVER[$selector . '_USERNAME']}:{$_SERVER[$selector . '_PASSWORD']}@{$_SERVER[$selector . '_HOST']}:{$port}/{$_SERVER[$selector . '_DATABASE']}{$id}";
            }
        ];
    }
    public function messageBroker($external = false)
    {
        if ($external && isset($_SERVER['BROKER_HOST'])) {
            return [
                'class' => \yii\queue\amqp_interop\Queue::class,
                'driver' => yii\queue\amqp_interop\Queue::ENQUEUE_AMQP_BUNNY,
                'dsn' => 'amqp://' . $_SERVER['BROKER_USERNAME'] . ':' . $_SERVER['BROKER_PASSWORD'] . '@' . $_SERVER['BROKER_HOST'] . ':' . (isset($_SERVER['BROKER_PORT']) ? $_SERVER['BROKER_PORT'] : 5672) . '/' . $_SERVER['BROKER_VHOST'],
                'as log' => \yii\queue\LogBehavior::class,
                'heartbeat' => 60,
                'queueName' => $_SERVER['APP_CODE'] . '-queue',
                'exchangeName' => $_SERVER['APP_CODE'] . '-exchange',
            ];
        } else {
            $db = \Yii::createObject($this->dbDriver('CORE_DB'));
            // Check the driver name
            if ($db->driverName === 'mysql') {
                return [
                    'class' => yii\queue\db\Queue::class,
                    'db' => 'db', // Database connection component or its config
                    'tableName' => '{{%queue}}', // Table name
                    'channel' => $_SERVER['APP_CODE'] . '-queue', // Queue channel key
                    'mutex' => \yii\mutex\MysqlMutex::class, // Mutex used to sync queries
                    'as log' => \yii\queue\LogBehavior::class,
                ];
            } elseif ($db->driverName === 'pgsql') {
                return [
                    'class' => yii\queue\db\Queue::class,
                    'db' => 'db', // Database connection component or its config
                    'tableName' => '{{%queue}}', // Table name
                    'channel' => $_SERVER['APP_CODE'] . '-queue', // Queue channel key
                    'mutex' => \yii\mutex\PgsqlMutex::class, // Mutex used to sync queries
                    'as log' => \yii\queue\LogBehavior::class,
                ];
            } else {
                throw new \yii\base\InvalidConfigException('Queue component only supports MySQL and PostgreSQL databases.');
            }
        }
    }
    public function cache()
    {
        $tenantId = Yii::$app->tenant->id ?? 'default';
        $appCode  = $_SERVER['APP_CODE'] ?? 'system_cache';

        if (isset($_SERVER['CACHE_DRIVER']) && $_SERVER['CACHE_DRIVER'] === 'redis') {
            return [
                'class' => \yii\redis\Cache::class,
                'keyPrefix' => $appCode . ':tenant:' . $tenantId . ':runtime:cache:',
                'redis' => [
                    'class' => 'yii\redis\Connection',
                    'hostname' => $_SERVER['CACHE_HOST'] ?? '127.0.0.1',
                    'password' => $_SERVER['CACHE_PASSWORD'] ?? '',
                    'port'     => $_SERVER['CACHE_PORT'] ?? 6379,
                    'database' => $_SERVER['CACHE_DATABASE'] ?? 0,
                ],
            ];
        }

        return [
            'class' => \yii\caching\FileCache::class,
            'dirMode' => 0775,
            'fileMode' => 0664,
            'gcProbability' => 10,
            'keyPrefix' => strtolower($appCode . ':tenant:' . $tenantId . ':app_cache_'),
        ];
    }

    public function logger()
    {
        return [
            [
                'class' => 'helpers\log\DbTarget',
                'levels' => ['error', 'warning'],
                'except' => ['application'],
                'logVars' => [], // Disable logging GET/POST vars for privacy
                //'except' => ['yii\web\HttpException:404'], // Ignore 404s
            ],
        ];
    }
}
