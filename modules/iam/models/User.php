<?php

namespace iam\models;

use Yii;
use yii\db\ActiveRecord;
use Lcobucci\JWT\Token\Plain;
use yii\web\IdentityInterface;
use helpers\auth\jwt\BlacklistModel;
use Lcobucci\JWT\Validation\Validator;

class User extends ActiveRecord implements IdentityInterface
{
    use \helpers\traits\Status;

    const STATUS_INACTIVE  = 9;
    const STATUS_ACTIVE    = 10;
    const STATUS_DELETED   = 1;
    const STATUS_BANNED    = 6;
    const DEFAULT_GROUP = 'user';

    public $locked_until;
    public $protectedUsers = ['admin', 'superadmin', 'root'];
    public static function tableName()
    {
        return '{{%users}}';
    }

    public function behaviors()
    {
        $behaviors = [];

        if ($this->hasAttribute('created_at') && $this->hasAttribute('updated_at')) {
            $behaviors['timestamp'] = \yii\behaviors\TimestampBehavior::class;
        }

        return array_merge(parent::behaviors(), $behaviors);
    }

    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_INACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_BANNED]],
            ['is_deleted', 'default', 'value' => 0],
        ];
    }

    public function fields()
    {
        return [
            'username',
            'status' => function () {
                $status = $this->is_deleted ? self::STATUS_DELETED : $this->status;
                return $this->loadStatus('SC' . $status);
            },
            'profile' => function () {
                return $this->profile;
            },
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // IdentityInterface Methods
    // ──────────────────────────────────────────────────────────────

    public static function findIdentity($id)
    {
        return static::findOne([
            'user_id'     => $id,
            'status'      => self::STATUS_ACTIVE,
            'is_deleted'  => 0,
        ]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        $config = Yii::$app->jwtConfiguration;

        try {
            /** @var Plain $parsedToken */
            $parsedToken = $config->parser()->parse((string) $token);

            $validator = new Validator();
            if (!$validator->validate($parsedToken, ...$config->validationConstraints())) {
                return null;
            }

            $jti = $parsedToken->claims()->get('jti');
            if (BlacklistModel::isBlacklisted($jti)) {
                return null;
            }

            // 'sub' is string → cast to int
            $userId = (int) $parsedToken->claims()->get('sub');

            // Reuse findIdentity() — it already checks status + is_deleted
            return static::findIdentity($userId);
        } catch (\Throwable $e) {
            // Log if needed: Yii::error($e);
            return null;
        }
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    // ──────────────────────────────────────────────────────────────
    // Password & Tokens
    // ──────────────────────────────────────────────────────────────

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function generateEmailVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    public function removeVerificationToken()
    {
        $this->verification_token = null;
    }

    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        return $timestamp + strtotime('+' . Yii::$app->settings->get('password_reset_token_lifetime', 5) . ' minute') >= time();
    }
    public static function isVerificationTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        return $timestamp + strtotime('+' . Yii::$app->settings->get('verification_token_lifetime', 1) . ' hour') >= time();
    }

    // ──────────────────────────────────────────────────────────────
    // Relations
    // ──────────────────────────────────────────────────────────────

    public function getProfile()
    {
        return $this->hasOne(Profile::class, ['profile_id' => 'profile_id']);
    }

    // ──────────────────────────────────────────────────────────────
    // Password History
    // ──────────────────────────────────────────────────────────────
    public function updatePasswordHistory($password)
    {
        //Save new history record
        $history = new PasswordHistory();
        $history->user_id = $this->user_id;
        $history->old_password = Yii::$app->security->generatePasswordHash($password);
        $history->created_at = time();
        $history->updated_at = time();
        $history->save(false);

        //Get ID list of the latest 10 records
        $idsToKeep = PasswordHistory::find()
            ->select('password_history_id')
            ->where(['user_id' => $this->user_id])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10)
            ->column();

        // Delete all older entries 
        if (!empty($idsToKeep)) {
            PasswordHistory::deleteAll([
                'AND',
                ['user_id' => $this->user_id],
                ['NOT IN', 'password_history_id', $idsToKeep]
            ]);
        }
    }

    public function validatePasswordHistory($password)
    {
        $password_memory = (int) Yii::$app->settings->get('password_memory') ?? 5;
        if ($password_memory <= 0) {
            return true;
        }
        // Get last N passwords from history
        $recentPasswords = PasswordHistory::find()
            ->where(['user_id' => $this->user_id])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($password_memory)
            ->all();
        foreach ($recentPasswords as $record) {
            // Validate against hashed previous password
            if (Yii::$app->security->validatePassword($password, $record->old_password)) {
                // Password was used before
                return false;
            }
        }
        return true; // New password is acceptable
    }

    // ──────────────────────────────────────────────────────────────
    // Finders
    // ──────────────────────────────────────────────────────────────

    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE, 'is_deleted' => 0]);
    }

    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
            'is_deleted' => 0,
        ]);
    }

    public static function findByVerificationToken($token)
    {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE,
            'is_deleted' => 0,
        ]);
    }
}
