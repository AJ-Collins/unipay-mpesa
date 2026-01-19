<?php

namespace admin\models\static\settings;

use helpers\settings\SystemSettingsCore;

/**
 * @OA\Schema(
 *  schema="Mailer Settings",
 *  @OA\Property(property="from_name", type="string", title="From Name", description="The sender name that appears in emails", example="System Mailer"),
 *  @OA\Property(property="smtp_server", type="string", title="SMTP Server", description="The SMTP server address for sending emails", example="mail.example.com"),
 *  @OA\Property(property="smtp_port", type="integer", title="Port", description="The SMTP port number (typically 465 for SSL or 587 for TLS)", example="587"),
 *  @OA\Property(property="smtp_username", type="string", title="SMTP Username", description="The username for SMTP authentication", example="noreply@example.com"),
 *  @OA\Property(property="smtp_password", type="string", title="Password", description="The password for SMTP authentication (encrypted)", example="emailpassword"),
 *  @OA\Property(property="email_encryption", type="string", title="Encryption", description="The encryption method for SMTP connection (none, ssl, or tls)", example="tls"),
 * )
 */
class Mailer extends SystemSettingsCore
{

    protected function defineSettings(): array
    {
        return [
            'from_name' => [
                'default'     => \Yii::$app->name,
                'validations' => [
                    [['from_name'], 'string', 'max' => 255],
                ],
            ],
            'smtp_server' => [
                'default'     => 'smtp.gmail.com',
                'validations' => [
                    [['smtp_server'], 'string', 'max' => 255],
                ],
            ],
            'smtp_port' => [
                'default'     => '465',
                'validations' => [
                    [['smtp_port'], 'integer'],
                ],
            ],
            'smtp_username' => [
                'default'     => 'admin@crackit.co.ke',
                'validations' => [
                    [['smtp_username'], 'string', 'max' => 255],
                ],
            ],
            'smtp_password' => [
                'default'     => 'bcfqtlgnthblofta',
                'encrypted'   => true,
                'validations' => [
                    [['smtp_password'], 'string', 'max' => 255],
                ],
            ],
            'smtp_encryption' => [
                'default'     => 'tls',
                'items'       => ['none' => 'None', 'ssl' => 'SSL', 'tls' => 'TLS'],
                'validations' => [
                    [['smtp_encryption'], 'in', 'range' => ['none', 'ssl', 'tls']],
                ],
            ],
        ];
    }
}
