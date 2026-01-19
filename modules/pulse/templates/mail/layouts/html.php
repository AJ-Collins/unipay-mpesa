<?php

use yii\helpers\Html;

/** @var \yii\web\View $this view component instance */
/** @var \yii\mail\MessageInterface $message the message being composed */
/** @var string $content main view render result */

?>
<?php $this->beginPage() ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= Yii::$app->charset ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <style type="text/css">
        body { margin: 0; padding: 0; background-color: #f4f4f7; }
        table { border-collapse: collapse; }
        a { color: #007bff; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: Arial, sans-serif;">
    <?php $this->beginBody() ?>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <!-- Header / Logo -->
                    <tr>
                        <td align="center" style="padding: 40px 20px 20px; background-color: #007bff; color: #ffffff;">
                            <!-- Replace with your logo URL or Yii settings -->
                            <h1 style="margin: 0; font-size: 24px; font-weight: bold;">
                                <?= Html::encode(Yii::$app->name ?? 'Your App Name') ?>
                            </h1>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px 40px;">
                            <?= $content ?>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f8f9fa; text-align: center; font-size: 12px; color: #6c757d;">
                            <p style="margin: 0 0 10px;">
                                &copy; <?= date('Y') ?> <?= Html::encode(Yii::$app->name ?? 'Your Company') ?>. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>