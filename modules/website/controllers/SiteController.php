<?php

namespace website\controllers;

use Yii;
use yii\helpers\Url;

class SiteController extends \yii\web\Controller
{
    public $proxy = '/site/proxy?url=';

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }
    public function actionIndex()
    {
        if ($_SERVER['APP_ENVIRONMENT'] == 'dev') {
            $this->layout = '@website/views/layouts/main';
            return $this->render('@website/views/site/index');
        } else {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'ping'   => 'ok',
                'message'  => 'Welcome to '.Yii::$app->name.' API',
                'version'  => $_SERVER['APP_VERSION'], 
                'timestamp' => date('c'), // ISO 8601
            ];
        }
    }
    public function actionProxy()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;

        // ----------------------------------------
        // CORS
        // ----------------------------------------
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

        if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
            Yii::$app->response->statusCode = 200;
            return "";
        }

        // ----------------------------------------
        // Validate URL
        // ----------------------------------------
        $targetUrl = Yii::$app->request->get("url");

        if (!$targetUrl) {
            Yii::$app->response->statusCode = 400;
            return json_encode(["error" => "Missing 'url' parameter"]);
        }

        $targetUrl = urldecode($targetUrl);

        if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            Yii::$app->response->statusCode = 400;
            return json_encode(["error" => "Invalid URL", "received" => $targetUrl]);
        }

        // ----------------------------------------
        // Collect incoming headers
        // ----------------------------------------
        $forwardHeaders = [];
        $incomingHeaders = getallheaders();

        foreach ($incomingHeaders as $key => $value) {
            $lower = strtolower($key);

            if ($lower === "host") continue;
            if ($lower === "origin" || $lower === "referer") continue;

            $forwardHeaders[] = "$key: $value";
        }

        // ----------------------------------------
        // Request body
        // ----------------------------------------
        $body = file_get_contents("php://input");

        // ----------------------------------------
        // cURL request
        // ----------------------------------------
        $ch = curl_init($targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER["REQUEST_METHOD"]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            Yii::$app->response->statusCode = 502;
            return json_encode(["error" => curl_error($ch)]);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        $responseHeadersRaw = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);

        curl_close($ch);

        // ----------------------------------------
        // Forward headers
        // ----------------------------------------
        $responseHeaderLines = explode("\r\n", $responseHeadersRaw);
        foreach ($responseHeaderLines as $header) {
            if (stripos($header, "HTTP/") === 0) continue;
            if (stripos($header, "access-control-") === 0) continue;

            if (trim($header) !== "") header($header);
        }

        // ----------------------------------------
        // Send body
        // ----------------------------------------
        Yii::$app->response->statusCode = $httpCode;
        return $responseBody;
    }
    public function actionDocs($mod = false)
    {
        $modulePath = Yii::getAlias('@modules') . '/' . $mod;

        if (!is_dir($modulePath)) {
            throw new \yii\web\NotFoundHttpException('The documentation of this module does not exist.');
        }
        $rootPath = Yii::getAlias('@webroot/');
        $openapi = \OpenApi\Generator::scan([
            $rootPath . 'modules/' . $mod,
            $rootPath . 'config',
        ]);

        Yii::$app->response->headers->set('Access-Control-Allow-Origin', '*');
        Yii::$app->response->headers->set('Content-Type', 'application/json');

        $file = $rootPath . 'modules/website/docs/' . $mod . '-openapi-resource.json';
        file_put_contents($file, $openapi->toJson());

        return Yii::$app->response->sendFile($file, null, ['mimeType' => 'application/json', 'inline' => true]);
    }
    public function getDocs()
    {
        $modulesPath = Yii::getAlias('@modules');
        // Get all directories
        $dirs = array_filter(glob($modulesPath . '/*'), 'is_dir');
        $moduleNames = array_map('basename', $dirs);
        // Build associative array with URLs as keys
        $result = [];
        foreach ($moduleNames as $module) {
            if ($module === 'website') {
                continue;
            }
            $result[Url::to(['/' . $_SERVER['APP_VERSION'] . '/docs/openapi-resource.json', 'mod' => $module])] = strtoupper($module . ' Module');
        }
        $externalConfig = Yii::$app->params['externalDocs'] ?? [];
        $result = array_merge($result, $externalConfig);
        return $result;
    }
}
