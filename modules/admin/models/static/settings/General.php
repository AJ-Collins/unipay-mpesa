<?php

namespace admin\models\static\settings;

use Yii;
use helpers\settings\SystemSettingsCore;

/**
 * @OA\Schema(
 *  schema="General Settings",
 *  @OA\Property(property="business_name", type="string", title="Business Name", description="The name of the business", example="Acme Corp"),
 *  @OA\Property(property="physical_address", type="string", title="Physical Address", description="The physical address of the business", example="Mombasa"),
 *  @OA\Property(property="postal_address", type="string", title="Postal Address", description="The postal code (numbers and hyphens only)", example="0000"),
 *  @OA\Property(property="email_address", type="string", title="Email Address", description="The primary email address", example="info@example.com"),
 *  @OA\Property(property="website", type="string", title="Website", description="The business website URL (include http/https)", example="https://example.com"),
 *  @OA\Property(property="primary_mobile_number", type="string", title="Primary Mobile Number", description="The primary mobile number (Kenyan format)", example="+254700000000"),
 * )
 */
class General extends SystemSettingsCore
{

    protected function defineSettings(): array
    {
        return [
            'business_name' => [
                'default'     => Yii::$app->name,
                'validations' => [
                    [['business_name'], 'string', 'max' => 255],
                ],
            ],
            'physical_address' => [
                'default'     => 'Mombasa',
                'validations' => [
                    [['physical_address'], 'string', 'max' => 255],
                ],
            ],
            'postal_address' => [
                'default'     => '0000',
                'validations' => [
                    [['postal_address'], 'match', 'pattern' => '/^[0-9\-]+$/', 'message' => 'Must be a valid postal code'],
                ],
            ],
            'email_address' => [
                'default'     => 'info@example.com',
                'validations' => [
                    [['email_address'], 'required'],
                    [['email_address'], 'email', 'message' => 'Invalid email'],
                ],
            ],
            'support_email' => [
                'default'     => 'info@example.com',
                'validations' => [
                    [['support_email'], 'required'],
                    [['support_email'], 'email', 'message' => 'Invalid email'],
                ],
            ],
            'website' => [
                'default'     => 'https://example.com',
                'validations' => [
                    [['website'], 'url', 'message' => 'Enter a valid URL (include http/https)'],
                ],
            ],
            'primary_mobile_number' => [
                'default'     => '0700000000',
                'validations' => [
                    [['primary_mobile_number'], 'match', 'pattern' => '/^(?:\+254|0)[0-9]{9}$/', 'message' => 'Enter a valid Kenyan phone number'],
                ],
            ],
        ];
    }
}
