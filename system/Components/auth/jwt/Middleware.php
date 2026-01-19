<?php
// helpers/auth/jwt/Middleware.php

namespace helpers\auth\jwt;

use Yii;
use yii\base\ActionFilter;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Validator;
use iam\models\User;

class Middleware extends ActionFilter
{
    public function beforeAction($action)
    {
        $header = Yii::$app->request->headers->get('Authorization');
        if (!$header || !preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return $this->deny(401, 'Missing or invalid token');
        }

        $config = Yii::$app->jwtConfiguration; // Your JwtConfig component

        try {
            /** @var Plain $token */
            $token = $config->parser()->parse($m[1]);

            // Use validator() + LooseValidAt in config!
            $validator = new Validator();
            if (!$validator->validate($token, ...$config->validationConstraints())) {
                return $this->deny(401);
            }

            $jti = $token->claims()->get('jti');
            if (BlacklistModel::isBlacklisted($jti)) {
                return $this->deny(401, 'You have been logged out from all devices');
            }

            $user = User::findIdentity((int) $token->claims()->get('sub'));
            if (!$user) {
                return $this->deny(401, 'User not found or inactive');
            }

            Yii::$app->user->setIdentity($user);
            return true;

        } catch (\Throwable $e) {
            // Optional: log $e->getMessage() in production
            return $this->deny(401, 'Invalid token format');
        }
    }

    private function deny(int $code, $msg = false)
    {
        $controller = $this->owner ?? Yii::$app->controller;
        if ($controller && method_exists($controller, 'errorResponse')) {
            Yii::$app->response->data = $controller->errorResponse($code, false, $msg);
        } else {
            Yii::$app->response->data = ['error' => $msg];
        }
        Yii::$app->response->statusCode = $code;
        return false;
    }
}