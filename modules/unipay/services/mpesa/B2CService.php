<?php

namespace unipay\services\mpesa;

use Yii;
use unipay\models\MpesaTransaction;
use unipay\hooks\BaseModel;

class B2CService extends MpesaClient
{
    /**
     * Send money from the business shortcode to a customer phone.
     *
     * @param  float  $amount   Amount in KES
     * @param  string $phone    Recipient phone in format 254XXXXXXXXX
     * @param  string $remarks  Short description (max 100 chars)
     * @param  string $commandId  BusinessPayment | SalaryPayment | PromotionPayment
     * @return array  Safaricom response
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public function send(
        float $amount,
        string $phone,
        string $remarks = 'Disbursement',
        string $commandId = 'BusinessPayment'
    ): array {
        $this->validateRequiredEnv(['MPESA_INITIATOR', 'MPESA_SECURITY_CREDENTIAL', 'MPESA_SHORTCODE']);

        $phone = $this->normalisePhone($phone);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('[unipay] B2CService::send — amount must be > 0.');
        }

        $validCommands = ['BusinessPayment', 'SalaryPayment', 'PromotionPayment'];
        if (!in_array($commandId, $validCommands, true)) {
            throw new \InvalidArgumentException('[unipay] B2CService::send — invalid commandId: ' . $commandId);
        }

        // Create a PENDING transaction record before calling Safaricom
        $transaction = $this->createPendingTransaction('B2C', $amount, $phone);

        Yii::info("[unipay] B2C: sending KES {$amount} to {$phone}", __METHOD__);

        $response = $this->request('/mpesa/b2c/v3/paymentrequest', [
            'InitiatorName'      => $_SERVER['MPESA_INITIATOR'],
            'SecurityCredential' => $_SERVER['MPESA_SECURITY_CREDENTIAL'],
            'CommandID'          => $commandId,
            'Amount'             => (int)$amount,
            'PartyA'             => $_SERVER['MPESA_SHORTCODE'],
            'PartyB'             => $phone,
            'Remarks'            => substr($remarks, 0, 100),
            'QueueTimeOutURL'    => $this->callbackUrl('unipay/mpesa/b2c-timeout'),
            'ResultURL'          => $this->callbackUrl('unipay/mpesa/b2c-result'),
            'Occasion'           => 'Disbursement',
        ]);

        // Store conversation IDs so we can match the async callback
        $this->updateTransactionConversation($transaction, $response);

        return $response;
    }

    private function createPendingTransaction(string $type, float $amount, string $phone): MpesaTransaction
    {
        $tx           = new MpesaTransaction();
        $tx->type     = $type;
        $tx->amount   = $amount;
        $tx->phone    = $phone;
        $tx->status   = MpesaTransaction::STATUS_PENDING;
        $tx->created_at = time();
        $tx->updated_at = time();

        if (!$tx->save()) {
            Yii::error('[unipay] B2CService: failed to create pending transaction: ' . json_encode($tx->errors), __METHOD__);
        }

        return $tx;
    }

    private function updateTransactionConversation(MpesaTransaction $tx, array $response): void
    {
        $conversationId          = $response['ConversationID'] ?? null;
        $originatorConversationId = $response['OriginatorConversationID'] ?? null;

        if ($conversationId) {
            $tx->conversation_id             = $conversationId;
            $tx->originator_conversation_id  = $originatorConversationId;
            $tx->updated_at                  = time();

            if (!$tx->save()) {
                Yii::warning('[unipay] B2CService: could not update conversation IDs: ' . json_encode($tx->errors), __METHOD__);
            }
        }
    }

    private function normalisePhone(string $phone): string
    {
        $clean = BaseModel::normalisePhone($phone);
        if ($clean === null) {
            throw new \InvalidArgumentException(
                '[unipay] Invalid phone number: ' . $phone . '. Expected format: 2547XXXXXXXX'
            );
        }
        return $clean;
    }

    private function validateRequiredEnv(array $keys): void
    {
        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) {
                throw new \RuntimeException("[unipay] B2CService: environment variable {$key} is not set.");
            }
        }
    }
}