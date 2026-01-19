<?php

namespace helpers\log;

use Yii;
use helpers\log\AccessLogModel;

/**
 * Centralized access logger for consistent logging across the app.
 */
class AccessLog
{
    public const LOGIN        = 'login';
    public const LOGOUT       = 'logout';
    public const FAILED_LOGIN = 'failed_login';
    public const PAGE_VIEW    = 'page_view';
    public const PASSWORD_CHANGE = 'password_change';

    /**
     * Static logger method that can be called anywhere.
     *
     * @param string      $action      One of the AccessLog::ACTION_* constants.
     * @param string|null $description Human-readable description of what happened.
     * @param int|null    $userId      Optionally override user id (useful for logging system events).
     * @param array|null  $extra       Additional metadata stored in a JSON column.
     *
     * @return bool True if saved successfully, false if failed.
     */
    public static function log(string $action, ?string $description = null, ?int $userId = null, ?array $extra = null): bool
    {
        try {
            $resolvedUserId = $userId ?? (!Yii::$app->user->isGuest ? Yii::$app->user->id : null);
            $isConsole = Yii::$app->request->isConsoleRequest;

            // Build metadata automatically if not provided
            $meta = $extra ?? [];
            if (!$isConsole) {
                $meta['method'] = Yii::$app->request->method;
                $meta['url'] = Yii::$app->request->absoluteUrl ?? null;
                $meta['referrer'] = Yii::$app->request->referrer ?? null;
                $meta['controller'] = Yii::$app->controller->id ?? null;
                $meta['action'] = Yii::$app->controller->action->id ?? null;

                // Capture safe POST data (strip sensitive info)
                if (in_array($action, [self::LOGIN, self::FAILED_LOGIN], true)) {
                    $safePost = Yii::$app->request->post();
                    unset($safePost['password'], $safePost['password_hash']);
                    $meta['post_data'] = $safePost;
                }
            }

            $log = new AccessLogModel();
            $log->user_id = $resolvedUserId;
            $log->action = $action;
            $log->description = $description;
            $log->extra_data = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_SLASHES) : null;
            $log->ip_address = $isConsole ? 'console' : Yii::$app->request->userIP;
            $log->user_agent = $isConsole ? 'console' : Yii::$app->request->userAgent;

            if (!$log->save()) {
                Yii::error("AccessLog save failed: " . json_encode($log->errors), __METHOD__);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Yii::error("AccessLog::log failed: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}
