<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $model */  // Assuming your User model has verification_token

$activationLink = Yii::$app->urlManager->createAbsoluteUrl(['/auth/activate', 'token' => $model->verification_token]);  // Adjust route as needed

?>
<div style="text-align: center; font-size: 16px; line-height: 1.6; color: #333333;">
    <h2 style="font-size: 28px; margin-bottom: 20px; color: #212529;">
        Welcome, <?= Html::encode($model->profile->full_name ?? $model->username ?? $model->email) ?>!
    </h2>

    <p style="margin-bottom: 20px; font-size: 18px;">
        Thank you for signing up. We're excited to have you on board!
    </p>

    <p style="margin-bottom: 40px;">
        To get started, please activate your account by clicking the button below.
    </p>

    <p style="margin-bottom: 40px;">
        <a href="<?= Html::encode($activationLink) ?>" 
           style="background-color: #28a745; color: #ffffff; padding: 16px 36px; font-size: 18px; font-weight: bold; text-decoration: none; border-radius: 6px; display: inline-block;">
            Activate Your Account
        </a>
    </p>

    <p style="margin-bottom: 20px; font-size: 14px; color: #6c757d;">
        This link will expire in <?= Yii::$app->settings->get('verification_token_lifetime', 1) ?> hours for security reasons.
    </p>

    <p style="margin: 30px 0; padding: 20px; background-color: #d4edda; border-radius: 6px; font-size: 14px; color: #155724; border: 1px solid #c3e6cb;">
        <strong>Almost there!</strong><br>
        Once activated, you'll have full access to your account and all features.
    </p>

    <p style="margin-top: 30px; font-size: 14px; color: #6c757d;">
        If you didn't create an account, you can safely ignore this email.<br>
        Questions? Contact our support team at 
        <a href="mailto:<?php echo Yii::$app->settings->get('support_email', 'support@example.com'); ?>" style="color: #007bff;"><?php echo Yii::$app->settings->get('support_email', 'support@example.com'); ?></a>.
    </p>
</div>