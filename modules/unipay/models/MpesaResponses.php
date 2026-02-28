<?php

namespace unipay\models;

/**
 *@OA\Schema(
 *  schema="MpesaResponses",
 *  @OA\Property(property="id", type="integer",title="Id", example="integer"),
 *  @OA\Property(property="transaction_id", type="integer",title="Transaction id", example="integer"),
 *  @OA\Property(property="raw_payload", type="string",title="Raw payload", example="string"),
 *  @OA\Property(property="result_code", type="int",title="Result code", example="int"),
 *  @OA\Property(property="result_desc", type="string",title="Result desc", example="string"),
 *  @OA\Property(property="created_at", type="integer",title="Created at", example="integer"),
 *  @OA\Property(property="updated_at", type="integer",title="Updated at", example="integer"),
 * )
 */

class MpesaResponses extends \unipay\hooks\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%mpesa_responses}}';
    }
    /**
     * list of fields to output by the payload.
     */
    public function fields()
    {
        return  
            [
            'transaction_id',
            'raw_payload',
            'result_code',
            'result_desc',
            ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['transaction_id', 'created_at', 'updated_at'], 'required'],
            [['transaction_id', 'result_code', 'created_at', 'updated_at'], 'default', 'value' => null],
            [['transaction_id', 'result_code', 'created_at', 'updated_at'], 'integer'],
            [['raw_payload'], 'safe'],
            [['result_desc'], 'string', 'max' => 255],
            [['transaction_id'], 'exist',
                'skipOnError'     => true,
                'targetClass'     => MpesaTransaction::class,
                'targetAttribute' => ['transaction_id' => 'id'],
            ],
        ];
    }

    /**
     * Gets query for [[Transaction]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTransaction()
    {
        return $this->hasOne(MpesaTransactions::class, ['id' => 'transaction_id']);
    }

    /**
     * Helper
     * Decode the JSON raw_payload into an array.
     */
    public function getPayloadArray(): array
    {
        if (empty($this->raw_payload)) {
            return [];
        }
        $decoded = json_decode($this->raw_payload, true);
        return (json_last_error() === JSON_ERROR_NONE) ? ($decoded ?? []) : [];
    }
}
