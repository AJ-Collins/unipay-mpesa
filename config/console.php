<?php

require_once __DIR__ . '/wrapper.php';
$wrapper = new ConfigWrapper();
$config = [
    'id' => $_SERVER['APP_CODE'] . '-console',
    'controllerNamespace' => 'ConsoleX\controllers',
    'controllerMap' => [
        'voyage' => [
            'class' => 'ConsoleX\controllers\VoyageController',
            'migrationPath' => $wrapper->load('migrationPaths'),
            'templateFile' => '@OmniCraft/migrations/template.php'
        ],
    ],
    'components' => [
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'baseUrl' => $_SERVER['APP_BASE_URL'],
            'hostInfo' => $_SERVER['APP_BASE_URL'],
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
    ]
];


if ($_SERVER['APP_ENVIRONMENT'] === 'dev') {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['*'],
        'generators' => [
            'model' => [
                'class'     =>  \OmniCraft\model\Generator::class,
                'useTablePrefix' => true,
            ],
            'crud' => [
                'class'     =>  \OmniCraft\crud\Generator::class,
            ],
            'module' => [
                'class'     =>  \OmniCraft\module\Generator::class,
            ],
            // 'job' => [
            //     'class'     => \yii\queue\gii\Generator::class,
            // ],
        ],
    ];
}
return $config;
