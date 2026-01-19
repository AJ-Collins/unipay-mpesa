<?php
require_once __DIR__ . '/wrapper.php';
$wrapper = new ConfigWrapper();

$config = [
    'components' => [
        'jwtConfiguration' => [
            'class' => \helpers\auth\jwt\Configuration::class,
        ],
    ],
    'params' => [
        'safeEndpoints' => [
            'error',
            'login',
            'refresh',
            'register',
            'options',
            'request-password-reset',
            'reset-password',
            'activate',
        ],
        'jwtAccessTokenTtl' => 60,
        'jwtRefreshTokenTtl' => 7776000,
        'jwtSecret' => hash_hmac('sha256', md5(__DIR__), sha1(__FILE__)),
        'externalDocs' => [
            //'documentation url' => 'Documentation Name',
        ],
    ],
];

return $config;
