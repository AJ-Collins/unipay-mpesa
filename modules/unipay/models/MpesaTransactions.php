<?php

namespace unipay\models;

/**
 *@OA\Schema(
 *  schema="MpesaTransactions",
 *  @OA\Property(property="id", type="integer",title="Id", example="integer"),
 *  @OA\Property(property="user_id", type="int",title="User id", example="int"),
 *  @OA\Property(property="type", type="string",title="Type", example="string"),
 *  @OA\Property(property="mpesa_receipt", type="string",title="Mpesa receipt", example="string"),
 *  @OA\Property(property="amount", type="float",title="Amount", example="float"),
 *  @OA\Property(property="phone", type="string",title="Phone", example="string"),
 *  @OA\Property(property="status", type="string",title="Status", example="string"),
 *  @OA\Property(property="conversation_id", type="string",title="Conversation id", example="string"),
 *  @OA\Property(property="originator_conversation_id", type="string",title="Originator conversation id", example="string"),
 *  @OA\Property(property="created_at", type="integer",title="Created at", example="integer"),
 *  @OA\Property(property="updated_at", type="integer",title="Updated at", example="integer"),
 * )
 */

class MpesaTransactions extends \unipay\hooks\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%mpesa_transactions}}';
    }
    /**
     * list of fields to output by the payload.
     */
    public function fields()
    {
        return  
            [
            'user_id',
            'type',
            'mpesa_receipt',
            'amount',
            'phone',
            'status' => function () {
                    return $this->recordStatus;
                },
            'conversation_id',
            'originator_conversation_id',
            ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'created_at', 'updated_at'], 'default', 'value' => null],
            [['user_id', 'created_at', 'updated_at'], 'integer'],
            [['type', 'amount', 'created_at', 'updated_at'], 'required'],
            [['amount'], 'number'],
            [['type'], 'string', 'max' => 10],
            [['mpesa_receipt'], 'string', 'max' => 50],
            [['phone', 'status'], 'string', 'max' => 20],
            [['conversation_id', 'originator_conversation_id'], 'string', 'max' => 100],
            [['conversation_id'], 'unique'],
            [['mpesa_receipt'], 'unique'],
            [['originator_conversation_id'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['user_id' => 'user_id']],
        ];
    }
    

    /**
     * Gets query for [[MpesaReconciliations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMpesaReconciliations()
    {
        return $this->hasMany(MpesaReconciliation::class, ['transaction_id' => 'id']);
    }

    /**
     * Gets query for [[MpesaResponses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMpesaResponses()
    {
        return $this->hasMany(MpesaResponses::class, ['transaction_id' => 'id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::class, ['user_id' => 'user_id']);
    }
}
