<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $model */

$resetLink =  Yii::$app->urlManager->createAbsoluteUrl(['/auth/reset-password', 'token' => $model->password_reset_token]);

?>
<div style="text-align: center; font-size: 16px; line-height: 1.6; color: #333333;">
    <h2 style="font-size: 24px; margin-bottom: 20px; color: #212529;">
        Hello <?= Html::encode($model->profile->full_name ?? $model->username) ?>,
    </h2>

    <p style="margin-bottom: 30px;">
        We received a request to reset the password for your account. 
        Click the button below to set a new password.
    </p>

    <p style="margin-bottom: 40px;">
        <a href="<?= Html::encode($resetLink) ?>" 
           style="background-color: #007bff; color: #ffffff; padding: 14px 32px; font-size: 16px; font-weight: bold; text-decoration: none; border-radius: 6px; display: inline-block;">
            Reset Your Password
        </a>
    </p>

    <p style="margin-bottom: 20px; font-size: 14px; color: #6c757d;">
        This link will expire in 1 hour for security reasons.
    </p>

    <p style="margin: 30px 0; padding: 20px; background-color: #e9ecef; border-radius: 6px; font-size: 14px;">
        <strong>Didn't request this change?</strong><br>
        If you didn't request a password reset, you can safely ignore this email. 
        Your password will remain unchanged, and no one can access your account without this link.
    </p>

    <p style="margin-top: 30px; font-size: 14px; color: #6c757d;">
        For your security, we recommend using a strong, unique password.        
    </p>
</div>