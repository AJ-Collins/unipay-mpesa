<?php

namespace pulse\jobs;

use Yii;
use Throwable;
use yii\base\BaseObject;

class MailerJob extends BaseObject implements \yii\queue\JobInterface
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
    public $to;
    public $subject;
    public $mailData;
    public $senderName = null;
    public $attachment = [];
    public $cc;
    public $template = 'default';
    public $dataObject = [
        'name' => 'mailData',
        'keys' => ['body']
    ];
    public $payload = [
        [
            'key' => 'to',
            'type' => 'tagsInput',
            'label' => 'Recipient Email',
            'validations' => [
                [['to'], 'required', 'message' => 'At least one recipient email is required'],
                [['to'], 'each', 'rule' => ['email', 'message' => '{value} is not a valid email address']],
            ]
        ],
        [
            'key' => 'cc',
            'label' => 'CC Recipients',
            'type' => 'tagsInput',
            'validations' => [
                [['cc'], 'each', 'rule' => ['email', 'message' => '{value} is not a valid email address']],
            ]
        ],
        [
            'key' => 'subject',
            'label' => 'Email Subject',
            'validations' => [
                [['subject'], 'required', 'message' => 'Email subject is required'],
            ]
        ],
        [
            'key' => 'template',
            'type' => 'dropDownList',
            'label' => 'Email Template',
            'predefinedOptions' =>  [
                'default' => 'Default',
            ],
            'validations' => [
                [['template'], 'required', 'message' => 'Select template to use'],
                [['template'], 'string', 'max' => 255],
            ]
        ],
        [
            'key' => 'body',
            'type' => 'textArea',
            'label' => 'Body',
            'validations' => [
                [['body'], 'safe'],
            ]
        ],
    ];
    public function init()
    {
        parent::init();
    }
    public function execute($queue)
    {
        try {
            $mailer = \Yii::createObject([
                'class' => 'helpers\Mail',
                'viewPath' => '@pulse/templates/mail',
            ]);

            if (empty($this->to)) {
                Yii::error("MailerJob missing 'to' address", __METHOD__);
                return;
            }

            $message = $mailer->compose(
                ['html' => $this->template],
                ['model' => (object) $this->mailData]
            )
                ->setTo($this->to)
                ->setFrom([Yii::$app->settings->get('smtp_username') => $this->senderName == null ? Yii::$app->name : $this->senderName])
                ->setSubject($this->subject);
            if (is_array($this->cc) && !empty($this->cc)) {
                $message->setCc($this->cc);
            }

            if (!empty($this->attachment)) {
                $attachments = is_array($this->attachment) ? $this->attachment : [$this->attachment];
                foreach ($attachments as $file) {
                    if (file_exists($file)) {
                        $message->attach($file, ['fileName' => basename($file)]);
                    } else {
                        Yii::error("Attachment file not found: {$file}", __METHOD__);
                    }
                }
            }

            if (!$mailer->send($message)) {
                Yii::error("Failed to send email to {$this->to}", __METHOD__);
            }
        } catch (Throwable $e) {
            Yii::error("MailerJob encountered an error: " . $e->getMessage(), __METHOD__);
            // Optionally rethrow if you want the job to be retried or handled by the queue system.
            throw $e;
        }
    }
    public function getTtr()
    {
        return 3 * 60;
    }
    public function canRetry($attempt, $error)
    {
        return ($attempt < 5) && ($error instanceof \Exception);
    }
}
