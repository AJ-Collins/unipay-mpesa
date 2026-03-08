<?php

namespace unipay\models;

/**
 *@OA\Schema(
 *  schema="MpesaReconciliation",
 *  @OA\Property(property="id", type="integer",title="Id", example="integer"),
 *  @OA\Property(property="transaction_id", type="integer",title="Transaction id", example="integer"),
 *  @OA\Property(property="mpesa_receipt", type="string",title="Mpesa receipt", example="string"),
 *  @OA\Property(property="amount", type="float",title="Amount", example="float"),
 *  @OA\Property(property="type", type="string",title="Type", example="string"),
 *  @OA\Property(property="phone", type="string",title="Phone", example="string"),
 *  @OA\Property(property="reconciled", type="boo",title="Reconciled", example="boo"),
 *  @OA\Property(property="reconciled_at", type="int",title="Reconciled at", example="int"),
 *  @OA\Property(property="notes", type="string",title="Notes", example="string"),
 *  @OA\Property(property="created_at", type="integer",title="Created at", example="integer"),
 * )
 */

class MpesaReconciliation extends \unipay\hooks\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%mpesa_reconciliation}}';
    }
    /**
     * list of fields to output by the payload.
     */
    public function fields()
    {
        return  
            [
            'transaction_id',
            'mpesa_receipt',
            'amount',
            'type',
            'phone',
            'reconciled',
            'reconciled_at',
            'notes',
            ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transaction_id', 'amount', 'type', 'created_at'], 'required'],
            [['transaction_id', 'reconciled_at', 'created_at'], 'default', 'value' => null],
            [['transaction_id', 'reconciled_at', 'created_at'], 'integer'],
            [['amount'], 'number'],
            [['reconciled'], 'boolean'],
            [['mpesa_receipt'], 'string', 'max' => 50],
            [['type'], 'string', 'max' => 10],
            [['phone'], 'string', 'max' => 20],
            [['notes'], 'string', 'max' => 500],
            [['mpesa_receipt'], 'unique'],
            [['transaction_id'], 'exist', 'skipOnError' => true, 'targetClass' => MpesaTransactions::class, 'targetAttribute' => ['transaction_id' => 'id']],
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
}
