<?php

namespace iam\models\static\auth;

use yii\base\Model;
use helpers\auth\jwt\BlacklistModel;
use helpers\auth\jwt\RefreshTokenModel;

class AuthModel extends Model
{
    /**
     * Blacklist every active jti from refresh tokens
     * → Makes ALL existing access tokens invalid immediately
     */
    protected function blacklistAllCurrentAccessTokens(int $userId): void
    {
        $activeRefreshTokens = RefreshTokenModel::find()
            ->select(['jti', 'expires_at'])
            ->where(['user_id' => $userId, 'is_revoked' => false])
            ->all();
        foreach ($activeRefreshTokens as $rt) {
            BlacklistModel::add($rt->jti, $rt->expires_at); // ← Adds to correct table
        }
        // Revoke all refresh tokens
        RefreshTokenModel::updateAll(
            ['is_revoked' => true],
            ['user_id' => $userId]
        );
    }
}
