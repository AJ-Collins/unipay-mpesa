<?php

namespace helpers\behaviors;

use Yii;
use yii\web\User;
use yii\base\Behavior;
use helpers\auth\LoginAttempt;
use helpers\log\AccessLog;

/**
 * Behavior to log user login/logout events using the centralized AccessLog.
 */
class Access extends Behavior
{
    public function events()
    {
        return [
            User::EVENT_BEFORE_LOGIN => 'beforeLogin',
            User::EVENT_AFTER_LOGIN  => 'afterLogin',
            User::EVENT_AFTER_LOGOUT => 'afterLogout',
        ];
    }

    /**
     * Helper to get a stable user id from the event identity or current user.
     */
    protected function resolveUserId($eventIdentity = null)
    {
        // try event identity first
        $identity = $eventIdentity ?? null;
        if ($identity !== null) {
            if (method_exists($identity, 'getId')) {
                return $identity->getId();
            }
            if (isset($identity->id)) {
                return $identity->id;
            }
            if (isset($identity->user_id)) {
                return $identity->user_id;
            }
        }

        // fallback to currently logged-in user
        return !Yii::$app->user->isGuest ? Yii::$app->user->id : null;
    }

    public function beforeLogin($event)
    {
        $identity = $event->identity;
        if ($identity && !empty($identity->is_locked) && !empty($identity->locked_until) && $identity->locked_until > time()) {
            $event->isValid = false;

            AccessLog::log(
                AccessLog::FAILED_LOGIN,
                'Login attempt on locked account',
                $this->resolveUserId($identity),
                [
                    'username' => $identity->username ?? null,
                    'locked_until' => $identity->locked_until,
                ]
            );

            return;
        }
    }

    public function afterLogin($event)
    {
        $userId = $this->resolveUserId($event->identity);

        AccessLog::log(
            AccessLog::LOGIN,
            'User logged in',
            $userId,
            [
                'session_id' => Yii::$app->session->getId(),
            ]
        );

        try {
            LoginAttempt::deleteAll(['ip_address' => Yii::$app->request->userIP]);
        } catch (\Throwable $e) {
            Yii::error('AccessLog: failed to clear LoginAttempt: ' . $e->getMessage(), __METHOD__);
        }
    }

    public function afterLogout($event)
    {
        $userId = $this->resolveUserId($event->identity);

        AccessLog::log(
            AccessLog::LOGOUT,
            'User logged out',
            $userId,
            [
                'session_id' => Yii::$app->session->getId(),
            ]
        );
    }
}
