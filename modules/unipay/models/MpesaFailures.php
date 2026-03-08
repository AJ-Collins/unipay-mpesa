<?php

namespace unipay\models;

/**
 *@OA\Schema(
 *  schema="MpesaFailures",
 *  @OA\Property(property="id", type="integer",title="Id", example="integer"),
 *  @OA\Property(property="transaction_id", type="int",title="Transaction id", example="int"),
 *  @OA\Property(property="type", type="string",title="Type", example="string"),
 *  @OA\Property(property="result_code", type="integer",title="Result code", example="integer"),
 *  @OA\Property(property="result_desc", type="string",title="Result desc", example="string"),
 *  @OA\Property(property="raw_payload", type="string",title="Raw payload", example="string"),
 *  @OA\Property(property="created_at", type="integer",title="Created at", example="integer"),
 * )
 */

class MpesaFailures extends \unipay\hooks\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%mpesa_failures}}';
    }
    /**
     * list of fields to output by the payload.
     */
    public function fields()
    {
        return  
            [
            'transaction_id',
            'type',
            'result_code',
            'result_desc',
            'raw_payload',
            ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transaction_id', 'result_code', 'created_at'], 'default', 'value' => null],
            [['transaction_id', 'result_code', 'created_at'], 'integer'],
            [['type', 'result_code', 'created_at'], 'required'],
            [['raw_payload'], 'safe'],
            [['type'], 'string', 'max' => 10],
            [['result_desc'], 'string', 'max' => 255],
        ];
    }
    
}
