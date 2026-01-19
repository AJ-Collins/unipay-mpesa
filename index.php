<?php
error_reporting(E_ALL ^ E_DEPRECATED);
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, "omnibase.cfg");
$dotenv->safeLoad();
 if (isset($_SERVER['APP_ENVIRONMENT']) && $_SERVER['APP_ENVIRONMENT'] == 'dev') {
    defined('YII_DEBUG') or define('YII_DEBUG', true);
    defined('YII_ENV') or define('YII_ENV', 'dev');
 }
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/config/common.php',
    require __DIR__ . '/config/web.php',
    require __DIR__ . '/config/api.php',
);
(new \yii\web\Application($config))->run();
