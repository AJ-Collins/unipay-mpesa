<?php
// iam/models/static/auth/Register.php

namespace iam\models\static\auth;

use Yii;
use yii\base\Model;
use iam\models\User;
use helpers\auth\Item;
use iam\models\Profile;
use pulse\jobs\MailerJob;
use iam\hooks\AuthConfigs;
use iam\models\static\rbac\AuthItem;

/**
 * @OA\Schema(
 *     schema="Register",
 *     @OA\Property(property="first_name", type="string", example="John", description="User's first name"),
 *     @OA\Property(property="middle_name", type="string", example="Michael", nullable=true, description="User's middle name"),
 *     @OA\Property(property="last_name", type="string", example="Doe", description="User's last name"),
 *     @OA\Property(property="email_address", type="string", format="email", example="john.doe@example.com", description="Valid email address"),
 *     @OA\Property(property="mobile_number", type="string", example="+2541700000000",   description="Valid mobile number with country code"),
 *     @OA\Property(property="username", type="string", example="johndoe",  description="Desired username"),
 *     @OA\Property(property="password", type="string", format="password", example="@dmiN1234$",  description="Password must be at least 8 characters and contain uppercase, lowercase, number, and special character."),
 *     @OA\Property(property="confirm_password", type="string", format="password", example="@dmiN1234$", description="Must match the password."),
 * )
 */
class Register extends Model
{
    public $first_name;
    public $middle_name;
    public $last_name;
    public $email_address;
    public $mobile_number;
    public $username;
    public $password;
    public $confirm_password;

    private ?Profile $_profile = null;

    public function rules()
    {
        return [
            // Required fields
            [['first_name', 'last_name', 'email_address', 'mobile_number', 'username', 'password', 'confirm_password'], 'required'],

            // Profile fields
            [['first_name', 'last_name'], 'string', 'max' => 50],
            ['middle_name', 'string', 'max' => 50],
            ['middle_name', 'default', 'value' => null],

            ['email_address', 'trim'],
            ['email_address', 'email'],
            ['email_address', 'unique', 'targetClass' => Profile::class, 'targetAttribute' => 'email_address', 'message' => 'This email is already registered.'],

            ['mobile_number', 'trim'],
            ['mobile_number', 'string', 'min' => 10, 'max' => 15],
            ['mobile_number', 'match', 'pattern' => '/^\+?[0-9]{10,15}$/', 'message' => 'Invalid mobile number format.'],
            ['mobile_number', 'unique', 'targetClass' => Profile::class, 'targetAttribute' => 'mobile_number', 'message' => 'This mobile number is already registered.'],

            // Username
            ['username', 'trim'],
            ['username', 'string', 'min' => 4, 'max' => 64],
            ['username', 'unique', 'targetClass' => User::class, 'message' => 'This username is already taken.'],

            // Password strength (strong policy)
            ['password', 'string', 'min' => 8],
            [
                'password',
                'match',
                'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
                'message' => 'Password must be at least 8 characters and contain: uppercase, lowercase, number, and special character.'
            ],

            // Confirm password
            ['confirm_password', 'compare', 'compareAttribute' => 'password', 'message' => "Passwords don't match"],
        ];
    }

    /**
     * Register user: Create profile first → then user
     * @return User|null
     */
    public function register(): ?User
    {
        if (!$this->validate()) {
            return null;
        }

        // Start transaction for atomicity
        $transaction = Yii::$app->db->beginTransaction();

        try {
            // 1. Create Profile first (all required fields)
            $profile = new Profile();
            $profile->first_name     = $this->first_name;
            $profile->middle_name    = $this->middle_name ?: null;
            $profile->last_name      = $this->last_name;
            $profile->email_address  = $this->email_address;
            $profile->mobile_number  = $this->mobile_number;

            if (!$profile->save(false)) {
                $transaction->rollBack();
                $this->addError('email_address', 'Unable to create profile.');
                return null;
            }

            // 2. Create User linked to profile
            $user = new User();
            $user->username    = $this->username;
            $user->profile_id  = $profile->profile_id;
            $user->setPassword($this->password);
            $user->generateAuthKey();
            $user->generateEmailVerificationToken();
            $user->status      = User::STATUS_INACTIVE; // or STATUS_INACTIVE for verification

            if (!$user->save(false)) {
                $transaction->rollBack();
                $this->addError('username', 'Unable to create account.');
                return null;
            }
            $transaction->commit();
            $this->_profile = $profile;
            // Assign default role to user
            $auth = AuthConfigs::authManager();
            try {
                $usrGroup = method_exists($auth, 'getGroup') ? $auth->getGroup(Yii::$app->user->identityClass::DEFAULT_GROUP) : null;
                if ($usrGroup !== null) {
                    if (method_exists($auth, 'assign')) {
                        $auth->assign($usrGroup, $user->user_id);
                        Yii::$app->queue->push(new MailerJob([
                            'to' => $this->_profile->email_address,
                            'subject' => 'New Account Registration',
                            'mailData' => $user,
                            'template' => 'account_activation',
                        ]));
                    }
                } else {
                    Yii::warning("Default 'user' group not found; skipping role assignment for username: {$user->username}");
                }
            } catch (\Throwable $e) {
                Yii::error("Failed to assign default group to user '{$user->username}': " . $e->getMessage());
            }
            return $user;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Registration failed: ' . $e->getMessage());
            $this->addError('username', 'Registration failed. Please try again.');
            return null;
        }
    }

    /**
     * Get the created profile
     */
    public function getProfile(): ?Profile
    {
        return $this->_profile;
    }
}
