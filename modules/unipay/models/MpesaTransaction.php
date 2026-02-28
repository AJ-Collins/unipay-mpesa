<?php

namespace unipay\models;

/**
 *@OA\Schema(
 *  schema="MpesaTransaction",
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

class MpesaTransaction extends \unipay\hooks\BaseModel
{
    /** Transaction types */
    const TYPE_C2B = 'C2B';
    const TYPE_B2C = 'B2C';
    const TYPE_B2B = 'B2B';

    /** Status values */
    const STATUS_PENDING   = 'PENDING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_FAILED    = 'FAILED';
    const STATUS_TIMEOUT   = 'TIMEOUT';

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
    public function fields(): array
    {
        return  
            [
            'user_id',
            'type',
            'mpesa_receipt',
            'amount',
            'phone',
            'status' => function () {
                    return $this->recordStatus ?? $this->status;
                },
            'conversation_id',
            'originator_conversation_id',
            'created_at',
            'updated_at',
            ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['type', 'amount', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'created_at', 'updated_at'], 'default', 'value' => null],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['user_id', 'created_at', 'updated_at'], 'integer'],
            [['amount'], 'number', 'min' => 0],
            [['type'],                       'string', 'max' => 10],
            [['mpesa_receipt'],              'string', 'max' => 50],
            [['phone', 'status'],            'string', 'max' => 20],
            [['conversation_id',
              'originator_conversation_id'], 'string', 'max' => 100],
            [['type'], 'in', 'range' => [self::TYPE_C2B, self::TYPE_B2C, self::TYPE_B2B]],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING,
                self::STATUS_COMPLETED,
                self::STATUS_FAILED,
                self::STATUS_TIMEOUT,
            ]],
            [['conversation_id'],            'unique'],
            [['mpesa_receipt'],              'unique'],
            [['originator_conversation_id'], 'unique'],
            [['user_id'], 'exist',
                'skipOnError'     => true,
                'targetClass'     => Users::class,
                'targetAttribute' => ['user_id' => 'user_id'],
            ],
        ];
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

    /**
     * Scopes
     */
    public static function findByConversationId(string $conversationId): ?self
    {
        return static::findOne(['conversation_id' => $conversationId]);
    }

    public static function findByReceipt(string $receipt): ?self
    {
        return static::findOne(['mpesa_receipt' => $receipt]);
    }
}
