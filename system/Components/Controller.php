<?php

namespace helpers;

use Yii;
use yii\filters\Cors;
use helpers\log\AccessLog;
use helpers\auth\jwt\Middleware;
use yii\rest\Controller as BaseController;

/**
 * Base controller class for REST API endpoints
 * 
 * Provides common functionality including:
 * - JWT authentication
 * - CORS configuration
 * - Access logging
 * - Standardized response formatting
 * - Error handling
 */
class Controller extends BaseController
{
    use \helpers\traits\Keygen;
    
    /** @var bool Disable CSRF validation for API endpoints */
    public $enableCsrfValidation = false;
    
    /** @var bool Enable/disable access logging for this controller */
    public $accessLog = true;
    
    /**
     * Configure controller behaviors
     * 
     * @return array Behavior configuration
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // Remove default authenticator (we use our own JWT middleware)
        unset($behaviors['authenticator']);

        // Configure CORS with secure settings
        $allowedDomains = Yii::$app->params['allowedDomains'] ?? ['*'];
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin'                           => $allowedDomains,
                'Access-Control-Allow-Origin'      => $allowedDomains,
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age'           => 86400, // Cache preflight for 24 hours
                'Access-Control-Request-Headers'   => ['*'],
                'Access-Control-Request-Method'    => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Allow-Headers'     => ['Authorization', 'Content-Type', 'X-Requested-With'],
                'Access-Control-Expose-Headers'    => ['X-Pagination-Total-Count', 'X-Pagination-Page-Count'],
            ],
        ];

        // Apply custom JWT authentication middleware
        // Excludes endpoints listed in safeEndpoints config
        $behaviors['authenticator'] = [
            'class' => Middleware::class,
            'except' => Yii::$app->params['safeEndpoints'] ?? ['login', 'refresh', 'options'],
        ];

        return $behaviors;
    }
    
    /**
     * Log user access before action execution
     * 
     * @param \yii\base\Action $action The action to be executed
     * @return bool Whether the action should continue
     */
    public function beforeAction($action)
    {
        // Call parent first (important for filters like AccessControl)
        if (!parent::beforeAction($action)) {
            return false;
        }
        
        // Log authenticated user page visits
        if ($this->accessLog && Yii::$app instanceof \yii\web\Application && !Yii::$app->user->isGuest) {
            AccessLog::log(
                AccessLog::PAGE_VIEW,
                'Visited page: ' . Yii::$app->request->absoluteUrl,
                Yii::$app->user->id,
                [
                    'method' => Yii::$app->request->method,
                    'url' => Yii::$app->request->absoluteUrl,
                    'route' => Yii::$app->requestedRoute,
                    'referrer' => Yii::$app->request->referrer,
                ]
            );
        }

        return true;
    }
    
    /**
     * Validate model and return errors in JSON format
     * 
     * @param \yii\base\Model $model The model to validate
     * @return array|string Validation errors or success message
     */
    public function sudoValidate($model)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!$model->validate()) {
            return \yii\widgets\ActiveForm::validate($model);
        }
        return 'Validation passed';
    }
    
    // ──────────────────────────────────────────────────────────────
    // Response Helpers 
    // ──────────────────────────────────────────────────────────────

    /**
     * Generate standardized error response
     * 
     * @param int|array $errors HTTP status code or validation errors array
     * @param array|false $acidErrors Additional model errors to include
     * @param string|false $message Custom error message
     * @return array Formatted error response
     */
    public function errorResponse($errors, $acidErrors = false, $message = false): array
    {
        // Handle HTTP status code errors
        if (!is_array($errors)) {
            Yii::$app->response->statusCode = $errors;
            $errors = $this->getErrorPayload($errors);
            $errors['errors']['message'] = $message ? $message : $errors['errors']['message'];
            return $errors;
        }

        // Handle validation errors (422)
        Yii::$app->response->statusCode = 422;
        $errors = array_map(fn($v) => $v[0], $errors);

        // Append additional model errors if provided
        if (is_array($acidErrors)) {
            foreach ($acidErrors as $modelKey => $models) {
                foreach ($models as $index => $model) {
                    if ($model->hasErrors()) {
                        $errors[$modelKey][$index] = $model->getErrors();
                    }
                }
            }
        }
        
        $payload = ['errors' => $errors];
        
        // Add alertify notification if message provided
        if ($message) {
            $payload = array_merge($payload, $this->alertifyResponse([
                'message' => $message ?: 'Validation failed',
                'theme'   => 'danger'
            ])['alertifyPayload']);
        }

        return ['errorPayload' => $payload];
    }

    /**
     * Generate standardized success response with data
     * 
     * @param mixed $data Response data (model, array, or DataProvider)
     * @param array $options Response options (statusCode, oneRecord, message, theme)
     * @return array Formatted data response
     */
    public function payloadResponse($data, array $options = []): array
    {
        $options = array_merge([
            'statusCode' => 200,
            'oneRecord'  => true,
            'message'    => false,
            'theme'      => 'success',
        ], $options);

        Yii::$app->response->statusCode = $options['statusCode'];

        // Handle paginated data providers
        if (!$options['oneRecord'] && $data instanceof \yii\data\DataProviderInterface) {
            return [
                'dataPayload' => [
                    'data'           => $data->getModels() ?: [],
                    'countOnPage'    => $data->count,
                    'totalCount'     => $data->totalCount,
                    'perPage'        => $data->pagination->pageSize,
                    'totalPages'     => $data->pagination->pageCount,
                    'currentPage'    => $data->pagination->page + 1,
                    'paginationLinks' => $data->pagination->getLinks(false),
                ],
            ];
        }

        // Handle single record or simple data
        $response = ['dataPayload' => ['data' => $data]];
        
        // Add alertify notification if message provided
        if ($options['message']) {
            $response['dataPayload']['alertify'] = $this->alertifyResponse($options)['alertifyPayload'];
        }

        return $response;
    }

    /**
     * Generate alertify notification response
     * 
     * @param array $options Notification options (statusCode, theme, type, message)
     * @return array Formatted alertify response
     */
    public function alertifyResponse(array $options = []): array
    {
        $defaults = [
            'statusCode' => 200,
            'theme'      => 'info',      // info, success, warning, danger
            'type'       => 'alert',     // alert, notify, confirm
            'message'    => null,
        ];

        $options = array_merge($defaults, $options);

        // Set HTTP status code
        Yii::$app->response->statusCode = $options['statusCode'];

        // Remove non-alertify keys from response
        unset($options['statusCode']);
        if (isset($options['oneRecord'])) {
            unset($options['oneRecord']);
        }

        return [
            'alertifyPayload' => $options,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────────────────────────────

    /**
     * Get absolute base URL for the application
     * 
     * @return string Base URL
     */
    public function baseUrl(): string
    {
        return \yii\helpers\Url::base(true);
    }

    /**
     * Get error message payload for HTTP status code
     * 
     * @param int $code HTTP status code
     * @return array Error payload with message
     */
    protected function getErrorPayload($code): array
    {
        $codes = [
            '400' => ['message' => 'Bad Request'],
            '401' => ['message' => 'Unauthorized'],
            '403' => ['message' => 'Forbidden'],
            '404' => ['message' => 'Not Found'],
            '422' => ['message' => 'Validation Failed'],
            '440' => ['message' => 'Session Expired'],
            '500' => ['message' => 'Server Error'],
        ];

        return ['errors' => $codes[$code] ?? $codes['500']];
    }
}
