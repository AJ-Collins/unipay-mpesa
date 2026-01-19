<?php

namespace helpers\auth\jwt;

use Yii;

class BlacklistModel extends \helpers\ActiveRecord
{
    
    public static function tableName()
    {
        return '{{%jwt_blacklist}}';
    }

    public static function add($jti, $expiresAt)
    {
        if (self::find()->where(['jti' => $jti])->exists()) {
            return;
        }
        $model = new self();
        $model->jti = $jti;
        $model->expires_at = $expiresAt;
        $model->created_at = time();
        $model->save(false);
        Yii::$app->cache->set("jwt_blacklist:{$jti}", true, $expiresAt - time() + 3600);
    }

    public static function isBlacklisted($jti)
    {
        $cached = Yii::$app->cache->get("jwt_blacklist:{$jti}");
        if ($cached !== false) return $cached;

        $exists = self::find()
            ->where(['jti' => $jti])
            ->andWhere(['>', 'expires_at', time()])
            ->exists();

        Yii::$app->cache->set("jwt_blacklist:{$jti}", $exists, 86400);
        return $exists;
    }
}
