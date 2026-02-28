<?php

/**
 * @OA\Server(
 *     description="API Development Server",
 *     url="http://localhost:8082/v2"
 * )
 * @OA\Server(
 *     description="API Staging Server",
 *     url="https://afya365.com/v2"
 * )
 */
require_once __DIR__ . '/wrapper.php';
$wrapper = new ConfigWrapper();
$config = [
    'id' => $_SERVER['APP_CODE'],
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'log'
    ],
    'controllerNamespace' => 'website\controllers',
    'params' => $wrapper->load('params'),
    'components' => [
        'assetManager' => [
            'basePath' => '@webroot/modules/website/assets',
            'baseUrl'  => '@web/modules/website/assets',
        ],
        'request' => [
            'class' => 'yii\web\Request',
            'enableCsrfValidation' => false,  // disable CSRF for API
            'enableCsrfCookie' => false,      // no CSRF cookie
            'cookieValidationKey' => hash_hmac(
                'sha256',
                md5(Yii::$app->name),
                'S3cR3t$alt2025!'
            ),
            'parsers' => [
                'application/json' => \yii\web\JsonParser::class,
            ],
            //'trustedHosts' => ['127.0.0.1', '::1', '172.20.0.0/24'],
        ],
        'response' => [
            'class' => 'yii\web\Response',
            'charset' => 'UTF-8',
            // DO NOT FORCE JSON globally
            // 'format' => \yii\web\Response::FORMAT_JSON,
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                // If this is HTML, skip modifying data
                if ($response->format === \yii\web\Response::FORMAT_HTML) {
                    return;
                }
                // Only force JSON when output is an array (API response)
                if (is_array($response->data)) {
                    $response->format = \yii\web\Response::FORMAT_JSON;
                    if (array_key_exists('code', $response->data)) {
                        unset($response->data['code']);
                    }
                    if (array_key_exists('status', $response->data)) {
                        $response->data['statusCode'] = $response->data['status'];
                        unset($response->data['status']);
                    }
                }
            },
        ],
        'user' => [
            'class' => \helpers\auth\AuthUser::class,
            //'identityClass' => 'admin\models\User',
            'enableAutoLogin' => false,   // No cookie-based auto login
            'enableSession' => false,     // Disable session for stateless API
            'loginUrl' => null,           // Prevent redirects to login page
            'as accessLog' => [
                'class' => \helpers\behaviors\Access::class, // optional: still log API access
            ],
        ],
        'errorHandler' => [
            'class' => 'yii\web\ErrorHandler',
            'errorAction' => null, // disable default site/error page
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                // Only handle API requests
                $request = Yii::$app->request;
                $isApi = strpos($request->url, '/v1/') === 0 || strpos($request->url, '/v2/') === 0;
                if (!$isApi) {
                    return; // keep normal HTML error for web pages
                }
                $response->format = \yii\web\Response::FORMAT_JSON;
                if ($response->statusCode >= 400) {
                    $response->data = [
                        'success' => false,
                        'statusCode' => $response->statusCode,
                        'name' => $response->statusText,
                        'message' => $response->data['message'] ?? $response->statusText,
                    ];
                }
            },
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'site/index',
                // OpenAPI docs
                [
                    'pattern' => $_SERVER['APP_VERSION'] . '/docs/openapi-resource',
                    'route' => 'site/docs',
                    'suffix' => '.json'
                ],
                // REST API rules
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'prefix' => $_SERVER['APP_VERSION'],
                    'controller' => $wrapper->load('controllers'), // e.g. ['users' => 'v1/users']
                    'extraPatterns' => $wrapper->load('routes'),
                    'tokens' => $wrapper->load('tokens'),
                ],
            ],
        ],

    ],

];
if ($_SERVER['APP_ENVIRONMENT'] == 'dev') {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['*'],
    ];
}
array_push($config,);
return $config;
