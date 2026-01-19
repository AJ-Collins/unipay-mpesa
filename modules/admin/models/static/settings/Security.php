<?php

namespace admin\models\static\settings;

use helpers\settings\SystemSettingsCore;
/**
 * @OA\Schema(
 *  schema="Privacy & Security",
 *  @OA\Property(property="otp_expiry", type="integer", title="OTP Expiry", description="OTP expiry time in minutes", example=1, minimum=1, maximum=60),
 *  @OA\Property(property="trusted_device_duration", type="integer", title="Trusted Device Duration", description="Trusted device duration in days", example=30, minimum=1, maximum=60),
 *  @OA\Property(property="maximum_login_attempts", type="integer", title="Maximum Login Attempts", description="Maximum allowed login attempts", example=2, minimum=1, maximum=20),
 *  @OA\Property(property="profile_lock_duration", type="integer", title="Account Lock Duration", description="Account lock duration in minutes", example=1, minimum=1, maximum=1440),
 *  @OA\Property(property="two_factor_auth", type="boolean", title="Two-Factor Authentication", description="Enable two-factor authentication", example=0),
 * )
 */
class Security extends SystemSettingsCore
{
    protected function defineSettings(): array
    {
        return [
            'otp_expiry' => [
                'default'     => 1,
                'validations' => [
                    [['otp_expiry'], 'integer', 'min' => 1, 'max' => 60],
                ],
            ],
            'trusted_device_duration' => [
                'default'     => 30,
                'validations' => [
                    [['trusted_device_duration'], 'integer', 'min' => 1, 'max' => 60],
                ],
            ],
            'maximum_login_attempts' => [
                'default'     => 2,
                'validations' => [
                    [['maximum_login_attempts'], 'integer', 'min' => 1, 'max' => 20],
                ],
            ],
            'profile_lock_duration' => [
                'default'     => 1,
                'validations' => [
                    [['profile_lock_duration'], 'integer', 'min' => 1, 'max' => 1440],
                ],
            ],
            'two_factor_auth' => [
                'default'     => 0,
                'validations' => [
                    [['two_factor_auth'], 'boolean'],
                ],
            ],
        ];
    }
}
