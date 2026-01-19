<?php

namespace iam\models\static\auth;

use Yii;
use yii\base\Model;
use iam\models\User;

/**
 *@OA\Schema(
 *  schema="Login",
 *  @OA\Property(property="username", type="string",title="Username", example="admin", description="Can be either username or email address."),
 *  @OA\Property(property="password", type="string",title="Password", example="@dmiN123",   description="User password."),
 * )
 */
class Login extends Model
{
    public $username;        // can be username or email
    public $password;
    public $rememberMe = false;

    private $_user;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['username', 'trim'],
            ['username', 'string', 'max' => 128],
            ['password', 'string', 'min' => 6],

            // Custom validation
            ['password', 'validatePassword'],
            ['username', 'validateAccountStatus'],
        ];
    }

    /**
     * Validates the password + rate limiting
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->logFailedAttempt();
                $this->addError($attribute, 'Incorrect username or password.');
                return;
            }

            // Check if account is locked due to too many failed attempts
            if ($user->is_locked && $user->locked_until && $user->locked_until > time()) {
                $minutes = ceil(($user->locked_until - time()) / 60);
                $this->addError('username', "Account locked. Try again in {$minutes} minute(s).");
                return;
            }
        }
    }

    /**
     * Check account status (deleted, inactive, etc.)
     */
    public function validateAccountStatus($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if ($user) {
                if ($user->is_deleted) {
                    $this->addError($attribute, 'Account has been deleted.');
                } elseif ($user->status != 10) {
                    $this->addError($attribute, 'Account is inactive.');
                }
            }
        }
    }

    /**
     * Logs a failed login attempt + enforces rate limiting
     */
    protected function logFailedAttempt()
    {
        $ip = Yii::$app->request->userIP;
        // If user not found, we still log the attempt without user_id
        Yii::$app->db->createCommand()->insert('{{%login_attempts}}', [
            'user_id' => null,
            'username' => $this->username,
            'ip_address' => $ip,
            'success' => false,
            'created_at' => time(),
        ])->execute();
        return;

        // Record attempt
        Yii::$app->db->createCommand()->insert('{{%login_attempts}}', [
            'user_id' => $this->_user ? $this->_user->user_id : null,
            'username' => $this->username,
            'ip_address' => $ip,
            'success' => false,
            'created_at' => time(),
        ])->execute();

        // Count failed attempts in last 15 minutes
        $threshold = 5; // max attempts
        $window = 15 * 60; // 15 minutes

        $failedCount = (int) Yii::$app->db->createCommand(
            "SELECT COUNT(*) FROM {{%login_attempts}} 
             WHERE ip_address = :ip 
               AND created_at > :time 
               AND success = 0",
            [':ip' => $ip, ':time' => time() - $window]
        )->queryScalar();

        if ($failedCount >= $threshold && $this->_user) {
            $lockTime = 15 * 60; // lock for 15 minutes
            $this->_user->is_locked = true;
            $this->_user->locked_until = time() + $lockTime;
            $this->_user->save(false);
        }
    }
    /**
     * Main login method – called from AuthController
     * @return bool
     */
    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            // Record successful login
            Yii::$app->db->createCommand()->insert('{{%login_attempts}}', [
                'user_id' => $user->user_id,
                'username' => $this->username,
                'ip_address' => Yii::$app->request->userIP,
                'success' => true,
                'created_at' => time(),
            ])->execute();

            // Update last login
            $user->last_login_at = time();
            $user->last_login_ip = Yii::$app->request->userIP;
            $user->is_locked = false;        // unlock if was previously locked
            $user->locked_until = null;
            $user->save(false);

            // Login into Yii identity (optional – only needed if you use Yii::$app->user elsewhere)
            return Yii::$app->user->login($user, $this->rememberMe ? 3600 * 24 * 30 : 0);
        }

        return false;
    }
    /**
     * Finds user by [[username]] or email
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::find()
                ->joinWith('profile') // assuming relation exists
                ->where(['username' => $this->username])
                ->orWhere(['email_address' => $this->username])
                ->andWhere(['is_deleted' => 0])
                ->one();
        }
        return $this->_user;
    }
}
