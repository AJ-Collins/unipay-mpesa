<?php

namespace unipay\hooks;

use Yii;
use unipay\models\MpesaTransaction;
use unipay\models\MpesaResponses;

/**
 * This is the base model class for unipay module.
 *
 * @OA\Info(
 *     description="API documentation for unipay module",
 *     version="1.0.0",
 *     title="unipay Module",
 *     @OA\Contact(
 *         email="douglasdaggs@gmail.com",
 *         name="Ananda Douglas"
 *     )
 * )
 */
class BaseModel extends \helpers\ActiveRecord
{
    /**
     * Log an M-Pesa transaction and its raw response payload.
     *
     * @param string      $type           C2B | B2C | B2B
     * @param array       $data           Parsed JSON payload from Safaricom
     * @param string|null $conversationId Optional ConversationID for B2C/B2B
     *
     * @return MpesaTransaction|null Returns the saved transaction or null on failure
     */
    public static function logMpesaTransaction(string $type, array $data, ?string $conversationId = null): ?MpesaTransaction
    {
        $transaction = null;
        $db = Yii::$app->db;
        $dbTransaction = $db->beginTransaction();

        try {
            // Resolve or create the MpesaTransaction record
            if ($conversationId) {
                // For B2C / B2B: try to match an existing record by ConversationID
                $transaction = MpesaTransaction::findOne(['conversation_id' => $conversationId]);
            }

            if ($transaction === null) {
                $transaction = new MpesaTransaction();
                $transaction->type       = strtoupper($type);
                $transaction->status     = 'PENDING';
                $transaction->created_at = time();
                $transaction->updated_at = time();

                // Populate amount & phone from C2B payload if present
                $transaction->amount = $data['TransAmount'] ?? $data['Amount'] ?? 0;
                $transaction->phone  = $data['MSISDN']      ?? $data['PartyB'] ?? null;

                if ($conversationId) {
                    $transaction->conversation_id = $conversationId;
                    $transaction->originator_conversation_id = $data['OriginatorConversationID'] ?? null;
                }

                if (!$transaction->save()) {
                    Yii::error(
                        '[unipay] MpesaTransaction save failed: ' . json_encode($transaction->errors),
                        __METHOD__
                    );
                    $dbTransaction->rollBack();
                    return null;
                }
            }

            // Update receipt / status when Safaricom confirms
            $resultCode = $data['ResultCode'] ?? $data['TransactionResultCode'] ?? null;

            if ($resultCode !== null) {
                $transaction->status     = ((int)$resultCode === 0) ? 'COMPLETED' : 'FAILED';
                $transaction->updated_at = time();

                $receipt = $data['MpesaReceiptNumber']
                    ?? $data['TransID']
                    ?? ($data['CallbackMetadata'] ? self::extractMetaItem($data['CallbackMetadata'], 'MpesaReceiptNumber') : null);

                if ($receipt) {
                    $transaction->mpesa_receipt = $receipt;
                }

                if (!$transaction->save()) {
                    Yii::error(
                        '[unipay] MpesaTransaction update failed: ' . json_encode($transaction->errors),
                        __METHOD__
                    );
                    $dbTransaction->rollBack();
                    return null;
                }
            }

            // Persist the raw response
            $response                 = new MpesaResponses();
            $response->transaction_id = $transaction->id;
            $response->raw_payload    = json_encode($data);
            $response->result_code    = isset($resultCode) ? (int)$resultCode : null;
            $response->result_desc    = $data['ResultDesc']
                ?? $data['TransactionResultDesc']
                ?? null;
            $response->created_at     = time();
            $response->updated_at     = time();

            if (!$response->save()) {
                Yii::error(
                    '[unipay] MpesaResponses save failed: ' . json_encode($response->errors),
                    __METHOD__
                );
                $dbTransaction->rollBack();
                return null;
            }

            $dbTransaction->commit();
            return $transaction;

        } catch (\Throwable $e) {
            $dbTransaction->rollBack();
            Yii::error(
                '[unipay] logMpesaTransaction exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                __METHOD__
            );
            return null;
        }
    }

    /**
     * Validate and normalise phone number to 254XXXXXXXXX format.
     *
     * Accepts:  07XXXXXXXX / +2547XXXXXXXX / 25471XXXXXXX / 01XXXXXXXX
     * Returns:  2547XXXXXXXX  (12 chars) or null if invalid
     */
    public static function normalisePhone(string $phone): ?string
    {
        $phone = preg_replace('/[\s\-\+]/', '', $phone);

        if (preg_match('/^0([71]\d{8})$/', $phone, $m)) {
            $phone = '254' . $m[1];
        }
        
        if (!preg_match('/^254[71]\d{8}$/', $phone)) {
            return null;
        }

        return $phone;
    }

    /**
     * Extract a named item from M-Pesa CallbackMetadata Item array.
     *
     * @param array  $metadata  e.g. ['Item' => [['Name'=>'MpesaReceiptNumber','Value'=>'LGR...'], ...]]
     * @param string $key
     * @return mixed|null
     */
    protected static function extractMetaItem(array $metadata, string $key)
    {
        foreach ($metadata['Item'] ?? [] as $item) {
            if (($item['Name'] ?? '') === $key) {
                return $item['Value'] ?? null;
            }
        }
        return null;
    }
}