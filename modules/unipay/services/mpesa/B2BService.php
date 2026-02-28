<?php

namespace unipay\services\mpesa;

use Yii;
use unipay\models\MpesaTransaction;

class B2BService extends MpesaClient
{
    /**
     * Transfer funds from your business shortcode to another business.
     *
     * @param  float  $amount            Amount in KES
     * @param  string $receiverShortcode Recipient Paybill or Till number
     * @param  string $accountRef        Account reference (visible on Safaricom statement)
     * @param  string $remarks           Short description (max 100 chars)
     * @param  string $commandId         BusinessToBusinessTransfer | MerchantToMerchantTransfer
     * @return array  Safaricom response
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public function transfer(
        float $amount,
        string $receiverShortcode,
        string $accountRef = '',
        string $remarks = 'B2B Transfer',
        string $commandId = 'BusinessToBusinessTransfer'
    ): array {
        $this->validateRequiredEnv(['MPESA_INITIATOR', 'MPESA_SECURITY_CREDENTIAL', 'MPESA_SHORTCODE']);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('[unipay] B2BService::transfer — amount must be > 0.');
        }

        if (empty($receiverShortcode)) {
            throw new \InvalidArgumentException('[unipay] B2BService::transfer — receiverShortcode is required.');
        }

        $validCommands = ['BusinessToBusinessTransfer', 'MerchantToMerchantTransfer'];
        if (!in_array($commandId, $validCommands, true)) {
            throw new \InvalidArgumentException('[unipay] B2BService::transfer — invalid commandId: ' . $commandId);
        }

        // Create a PENDING record before calling Safaricom
        $transaction = $this->createPendingTransaction($amount, $receiverShortcode);

        Yii::info("[unipay] B2B: sending KES {$amount} to shortcode {$receiverShortcode}", __METHOD__);

        $response = $this->request('/mpesa/b2b/v1/paymentrequest', [
            'Initiator'             => $_SERVER['MPESA_INITIATOR'],
            'SecurityCredential'    => $_SERVER['MPESA_SECURITY_CREDENTIAL'],
            'CommandID'             => $commandId,
            'SenderIdentifierType'  => '4',
            'RecieverIdentifierType'=> '4',
            'Amount'                => (int)$amount,
            'PartyA'                => $_SERVER['MPESA_SHORTCODE'],
            'PartyB'                => $receiverShortcode,
            'AccountReference'      => substr($accountRef, 0, 12),
            'Remarks'               => substr($remarks, 0, 100),
            'QueueTimeOutURL'       => $this->callbackUrl('unipay/mpesa/b2b-timeout'),
            'ResultURL'             => $this->callbackUrl('unipay/mpesa/b2b-result'),
        ]);

        $this->updateTransactionConversation($transaction, $response);

        return $response;
    }

    private function createPendingTransaction(float $amount, string $phone): MpesaTransaction
    {
        $tx             = new MpesaTransaction();
        $tx->type       = MpesaTransaction::TYPE_B2B;
        $tx->amount     = $amount;
        $tx->phone      = $phone; // stores the receiver shortcode in the phone column
        $tx->status     = MpesaTransaction::STATUS_PENDING;
        $tx->created_at = time();
        $tx->updated_at = time();

        if (!$tx->save()) {
            Yii::error('[unipay] B2BService: failed to create pending transaction: ' . json_encode($tx->errors), __METHOD__);
        }

        return $tx;
    }

    private function updateTransactionConversation(MpesaTransaction $tx, array $response): void
    {
        $conversationId           = $response['ConversationID'] ?? null;
        $originatorConversationId = $response['OriginatorConversationID'] ?? null;

        if ($conversationId) {
            $tx->conversation_id            = $conversationId;
            $tx->originator_conversation_id = $originatorConversationId;
            $tx->updated_at                 = time();

            if (!$tx->save()) {
                Yii::warning('[unipay] B2BService: could not update conversation IDs: ' . json_encode($tx->errors), __METHOD__);
            }
        }
    }

    private function validateRequiredEnv(array $keys): void
    {
        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) {
                throw new \RuntimeException("[unipay] B2BService: environment variable {$key} is not set.");
            }
        }
    }
}