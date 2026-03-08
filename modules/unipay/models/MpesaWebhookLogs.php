<?php

namespace unipay\models;

/**
 *@OA\Schema(
 *  schema="MpesaWebhookLogs",
 *  @OA\Property(property="id", type="integer",title="Id", example="integer"),
 *  @OA\Property(property="endpoint", type="string",title="Endpoint", example="string"),
 *  @OA\Property(property="ip_address", type="string",title="Ip address", example="string"),
 *  @OA\Property(property="raw_payload", type="string",title="Raw payload", example="string"),
 *  @OA\Property(property="safaricom_ip_ok", type="boo",title="Safaricom ip ok", example="boo"),
 *  @OA\Property(property="processed", type="boo",title="Processed", example="boo"),
 *  @OA\Property(property="error_message", type="string",title="Error message", example="string"),
 *  @OA\Property(property="created_at", type="integer",title="Created at", example="integer"),
 * )
 */

class MpesaWebhookLogs extends \unipay\hooks\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%mpesa_webhook_logs}}';
    }
    /**
     * list of fields to output by the payload.
     */
    public function fields()
    {
        return  
            [
            'endpoint',
            'ip_address',
            'raw_payload',
            'safaricom_ip_ok',
            'processed',
            'error_message',
            ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['endpoint', 'created_at'], 'required'],
            [['raw_payload'], 'safe'],
            [['safaricom_ip_ok', 'processed'], 'boolean'],
            [['created_at'], 'default', 'value' => null],
            [['created_at'], 'integer'],
            [['endpoint'], 'string', 'max' => 100],
            [['ip_address'], 'string', 'max' => 45],
            [['error_message'], 'string', 'max' => 500],
        ];
    }
    
}
