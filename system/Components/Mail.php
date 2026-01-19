<?php

namespace helpers;

use Yii;

class Mail extends \yii\symfonymailer\Mailer
{
    /* 
    Yii::$app->queue->push(new MailerJob([
        'to' => 'douglasdaggs@gmail.com',
        'subject' => 'Testing Mailer',
        'mailData' => '123456',
        'senderName' => 'Douglas Daggs',
        // 'attachment' => [
        //     Yii::getAlias('@storage/uploads/5177131557.pdf'),
        //     Yii::getAlias('@storage/uploads/qwerty.pdf'),          
        // ],
        'template' => 'password_reset',
    ])); 
    */
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
