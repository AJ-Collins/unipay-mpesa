<?php

namespace pulse\services;

use Yii;

class EmailService extends \yii\symfonymailer\Mailer
{
    
    public $useFileTransport = false;
    public $_transport;
    public function init()
    {
        $this->setTransport([
            'scheme' => 'smtps',
            'host' => Yii::$app->settings->get('smtp_server'),
            'username' => Yii::$app->settings->get('smtp_username'),
            'password' => Yii::$app->settings->get('smtp_password'),
            'port' => (int) Yii::$app->settings->get('smtp_port'),
            'encryption' => Yii::$app->settings->get('smtp_encryption'),
        ]);
        parent::init();
    }
}
