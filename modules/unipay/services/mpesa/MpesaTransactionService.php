<?php

namespace unipay\services\mpesa;

use Yii;
use unipay\models\MpesaTransactions;
use unipay\models\MpesaResponses;
use unipay\models\MpesaFailures;
use unipay\models\MpesaReconciliation;
use unipay\models\MpesaWebhookLogs;

/**
 * Orchestration layer: creates DB records, calls MpesaService, stores responses.
 * All public methods return ['success' => bool, 'data' => array, 'error' => string|null]
 */
class MpesaTransactionService
{
    private MpesaService $api;

    public function __construct()
    {
        $this->api = new MpesaService();
    }

    /**
     * Initiate STK Push and persist a PENDING transaction record.
     */
    public function stkPush(array $params, ?int $userId = null): array
    {
        // Format phone and store it
        try {
            $phone = $this->api->formatPhone($params['phone']);
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }

        $amount     = (float) $params['amount'];
        $accountRef = $params['account_ref'] ?? 'Payment';
        $desc       = $params['description']  ?? 'STK Push';

        // Call Safaricom
        $result = $this->api->stkPush($phone, $amount, $accountRef, $desc);

        if (!empty($result['error']) || empty($result['CheckoutRequestID'])) {
            return $this->handleApiError('C2B', $result);
        }

        // Persist transaction
        $tx = $this->createTransaction([
            'user_id'                    => $userId,
            'type'                       => 'C2B',
            'amount'                     => $amount,
            'phone'                      => $phone,
            'status'                     => 'PENDING',
            'conversation_id'            => $result['CheckoutRequestID'],
            'originator_conversation_id' => $result['MerchantRequestID'] ?? null,
        ]);

        return [
            'success'            => true,
            'data'               => $result,
            'transaction_id'     => $tx->id,
            'checkout_request_id' => $result['CheckoutRequestID'],
        ];
    }

    /**
     * Query STK push status and sync with our DB record.
     */
    public function stkQuery(string $checkoutRequestId): array
    {
        $result = $this->api->stkQuery($checkoutRequestId);

        // Sync status if we can find the transaction
        $tx = MpesaTransactions::find()
            ->where(['conversation_id' => $checkoutRequestId])
            ->one();

        if ($tx && isset($result['ResultCode'])) {
            $tx->status = $result['ResultCode'] == 0 ? 'SUCCESS' : 'FAILED';
            $tx->save(false);
        }

        return ['success' => true, 'data' => $result];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // C2B REGISTER URLs
    // ─────────────────────────────────────────────────────────────────────────

    public function c2bRegisterUrls(string $responseType = 'Completed'): array
    {
        $result = $this->api->c2bRegisterUrls($responseType);
        return ['success' => !isset($result['error']), 'data' => $result];
    }

    /**
     * Sandbox simulate C2B
     */
    public function c2bSimulate(array $params): array
    {
        try {
            $phone = $this->api->formatPhone($params['phone']);
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }

        $result = $this->api->c2bSimulate($phone, (float) $params['amount'], $params['bill_ref'] ?? 'TEST');
        return ['success' => !isset($result['error']), 'data' => $result];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // B2C
    // ─────────────────────────────────────────────────────────────────────────

    public function b2c(array $params, ?int $userId = null): array
    {
        try {
            $phone = $this->api->formatPhone($params['phone']);
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }

        $amount    = (float) $params['amount'];
        $commandId = $params['command_id'] ?? 'BusinessPayment';
        $remarks   = $params['remarks']    ?? 'B2C Payment';
        $occasion  = $params['occasion']   ?? '';

        $result = $this->api->b2c($phone, $amount, $commandId, $remarks, $occasion);

        if (!empty($result['error']) || empty($result['ConversationID'])) {
            return $this->handleApiError('B2C', $result);
        }

        $tx = $this->createTransaction([
            'user_id'                    => $userId,
            'type'                       => 'B2C',
            'amount'                     => $amount,
            'phone'                      => $phone,
            'status'                     => 'PENDING',
            'conversation_id'            => $result['ConversationID'],
            'originator_conversation_id' => $result['OriginatorConversationID'] ?? null,
        ]);

        return [
            'success'        => true,
            'data'           => $result,
            'transaction_id' => $tx->id,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // B2B
    // ─────────────────────────────────────────────────────────────────────────

    public function b2b(array $params, ?int $userId = null): array
    {
        $amount            = (float) $params['amount'];
        $receiverShortcode = $params['receiver_shortcode'];
        $commandId         = $params['command_id']      ?? 'BusinessPayBill';
        $remarks           = $params['remarks']         ?? 'B2B Payment';
        $accountRef        = $params['account_ref']     ?? '';

        $result = $this->api->b2b($receiverShortcode, $amount, $commandId, '4', '4', $remarks, $accountRef);

        if (!empty($result['error']) || empty($result['ConversationID'])) {
            return $this->handleApiError('B2B', $result);
        }

        $tx = $this->createTransaction([
            'user_id'                    => $userId,
            'type'                       => 'B2B',
            'amount'                     => $amount,
            'phone'                      => null,
            'status'                     => 'PENDING',
            'conversation_id'            => $result['ConversationID'],
            'originator_conversation_id' => $result['OriginatorConversationID'] ?? null,
        ]);

        return [
            'success'        => true,
            'data'           => $result,
            'transaction_id' => $tx->id,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TRANSACTION STATUS QUERY
    // ─────────────────────────────────────────────────────────────────────────

    public function transactionStatus(array $params): array
    {
        $result = $this->api->transactionStatus(
            $params['transaction_id'],
            $params['identifier_type'] ?? '4',
            $params['remarks']         ?? 'Status query',
            $params['occasion']        ?? ''
        );
        return ['success' => !isset($result['error']), 'data' => $result];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACCOUNT BALANCE
    // ─────────────────────────────────────────────────────────────────────────

    public function accountBalance(): array
    {
        $result = $this->api->accountBalance();
        return ['success' => !isset($result['error']), 'data' => $result];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REVERSAL
    // ─────────────────────────────────────────────────────────────────────────

    public function reversal(array $params): array
    {
        $result = $this->api->reversal(
            $params['transaction_id'],
            (float) $params['amount'],
            $params['receiver_id_type'] ?? '11',
            $params['remarks']          ?? 'Reversal',
            $params['occasion']         ?? ''
        );
        return ['success' => !isset($result['error']), 'data' => $result];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CALLBACK PROCESSING
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Process STK Push callback from Safaricom
     */
    public function processStkCallback(array $payload): void
    {
        $body = $payload['Body']['stkCallback'] ?? [];

        $checkoutId  = $body['CheckoutRequestID']  ?? null;
        $merchantId  = $body['MerchantRequestID']  ?? null;
        $resultCode  = $body['ResultCode']          ?? -1;
        $resultDesc  = $body['ResultDesc']          ?? '';

        // Find transaction by CheckoutRequestID (stored as conversation_id)
        $tx = MpesaTransactions::find()
            ->where(['conversation_id' => $checkoutId])
            ->one();

        if (!$tx) {
            Yii::warning("[STK Callback] Transaction not found for CheckoutRequestID: $checkoutId", 'mpesa');
            return;
        }

        $receipt = null;
        $amount  = $tx->amount;

        if ($resultCode == 0) {
            // Extract metadata items
            $items = $body['CallbackMetadata']['Item'] ?? [];
            $meta  = [];
            foreach ($items as $item) {
                $meta[$item['Name']] = $item['Value'] ?? null;
            }
            $receipt = $meta['MpesaReceiptNumber'] ?? null;
            $amount  = $meta['Amount']             ?? $amount;

            $tx->status        = 'SUCCESS';
            $tx->mpesa_receipt = $receipt;
            $tx->amount        = $amount;
        } else {
            $tx->status = 'FAILED';
        }

        $tx->save(false);

        // Store raw response
        $this->saveResponse($tx->id, $payload, $resultCode, $resultDesc);

        // Reconcile on success
        if ($resultCode == 0 && $receipt) {
            $this->reconcile($tx, $receipt);
        } else {
            $this->saveFailure($tx->id, 'C2B', $resultCode, $resultDesc, $payload);
        }
    }

    /**
     * Process C2B Confirmation callback
     */
    public function processC2bConfirmation(array $payload): void
    {
        $resultCode  = $payload['ResultCode']       ?? -1;
        $receipt     = $payload['TransID']          ?? null;
        $amount      = $payload['TransAmount']      ?? 0;
        $phone       = $payload['MSISDN']           ?? null;
        $billRef     = $payload['BillRefNumber']    ?? null;

        if ($phone) {
            try {
                $phone = $this->api->formatPhone($phone);
            } catch (\Exception) {}
        }

        // Find or create transaction
        $tx = MpesaTransactions::find()
            ->where(['mpesa_receipt' => $receipt])
            ->one();

        if (!$tx) {
            $tx = $this->createTransaction([
                'type'         => 'C2B',
                'amount'       => $amount,
                'phone'        => $phone,
                'status'       => 'SUCCESS',
                'mpesa_receipt' => $receipt,
            ]);
        } else {
            $tx->status = 'SUCCESS';
            $tx->save(false);
        }

        $this->saveResponse($tx->id, $payload, 0, 'C2B Confirmed');
        $this->reconcile($tx, $receipt);
    }

    /**
     * Process B2C result callback
     */
    public function processB2cResult(array $payload): void
    {
        $result      = $payload['Result']          ?? [];
        $resultCode  = $result['ResultCode']        ?? -1;
        $resultDesc  = $result['ResultDesc']        ?? '';
        $convId      = $result['ConversationID']    ?? null;
        $origConvId  = $result['OriginatorConversationID'] ?? null;

        $tx = $this->findTransaction($convId, $origConvId);
        if (!$tx) {
            return;
        }

        $receipt = null;
        if ($resultCode == 0) {
            $params  = $result['ResultParameters']['ResultParameter'] ?? [];
            $meta    = $this->extractResultParams($params);
            $receipt = $meta['TransactionReceipt'] ?? null;

            $tx->status        = 'SUCCESS';
            $tx->mpesa_receipt = $receipt;
        } else {
            $tx->status = 'FAILED';
        }

        $tx->save(false);
        $this->saveResponse($tx->id, $payload, $resultCode, $resultDesc);

        if ($resultCode == 0 && $receipt) {
            $this->reconcile($tx, $receipt);
        } else {
            $this->saveFailure($tx->id, 'B2C', $resultCode, $resultDesc, $payload);
        }
    }

    /**
     * Process B2B result callback
     */
    public function processB2bResult(array $payload): void
    {
        $result     = $payload['Result']          ?? [];
        $resultCode = $result['ResultCode']        ?? -1;
        $resultDesc = $result['ResultDesc']        ?? '';
        $convId     = $result['ConversationID']    ?? null;
        $origConvId = $result['OriginatorConversationID'] ?? null;

        $tx = $this->findTransaction($convId, $origConvId);
        if (!$tx) {
            return;
        }

        $receipt = null;
        if ($resultCode == 0) {
            $params  = $result['ResultParameters']['ResultParameter'] ?? [];
            $meta    = $this->extractResultParams($params);
            $receipt = $meta['TransactionReceipt'] ?? null;

            $tx->status        = 'SUCCESS';
            $tx->mpesa_receipt = $receipt;
        } else {
            $tx->status = 'FAILED';
        }

        $tx->save(false);
        $this->saveResponse($tx->id, $payload, $resultCode, $resultDesc);

        if ($resultCode == 0 && $receipt) {
            $this->reconcile($tx, $receipt);
        } else {
            $this->saveFailure($tx->id, 'B2B', $resultCode, $resultDesc, $payload);
        }
    }

    /**
     * Process generic timeout callback (B2C/B2B/etc.)
     */
    public function processTimeout(array $payload, string $type): void
    {
        $result     = $payload['Result'] ?? $payload;
        $convId     = $result['ConversationID']           ?? null;
        $origConvId = $result['OriginatorConversationID'] ?? null;

        $tx = $this->findTransaction($convId, $origConvId);
        if ($tx) {
            $tx->status = 'TIMEOUT';
            $tx->save(false);
        }

        $this->saveFailure($tx->id ?? null, $type, -1, 'Queue timeout', $payload);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function createTransaction(array $attrs): MpesaTransactions
    {
        $tx             = new MpesaTransactions();
        $tx->user_id    = $attrs['user_id']    ?? null;
        $tx->type       = $attrs['type'];
        $tx->amount     = $attrs['amount'];
        $tx->phone      = $attrs['phone']      ?? null;
        $tx->status     = $attrs['status']     ?? 'PENDING';
        $tx->mpesa_receipt              = $attrs['mpesa_receipt']              ?? null;
        $tx->conversation_id            = $attrs['conversation_id']            ?? null;
        $tx->originator_conversation_id = $attrs['originator_conversation_id'] ?? null;

        // Prevent duplicate conversation IDs (idempotency)
        if ($tx->conversation_id) {
            $existing = MpesaTransactions::find()
                ->where(['conversation_id' => $tx->conversation_id])
                ->one();
            if ($existing) {
                return $existing;
            }
        }

        $tx->save(false);
        return $tx;
    }

    private function saveResponse(int $txId, array $payload, int $resultCode, string $resultDesc): void
    {
        $resp              = new MpesaResponses();
        $resp->transaction_id = $txId;
        $resp->raw_payload    = $payload;
        $resp->result_code    = $resultCode;
        $resp->result_desc    = $resultDesc;
        $resp->created_at     = time();
        $resp->updated_at     = time();
        $resp->save(false);
    }

    private function saveFailure(?int $txId, string $type, int $resultCode, string $resultDesc, array $payload): void
    {
        $f              = new MpesaFailures();
        $f->transaction_id = $txId;
        $f->type           = $type;
        $f->result_code    = $resultCode;
        $f->result_desc    = mb_substr($resultDesc, 0, 255);
        $f->raw_payload    = $payload;
        $f->created_at     = time();
        $f->save(false);
    }

    private function reconcile(MpesaTransactions $tx, ?string $receipt): void
    {
        if (!$receipt) {
            return;
        }

        // Prevent duplicate reconciliation
        $exists = MpesaReconciliation::find()
            ->where(['mpesa_receipt' => $receipt])
            ->exists();

        if ($exists) {
            return;
        }

        $rec                = new MpesaReconciliation();
        $rec->transaction_id = $tx->id;
        $rec->mpesa_receipt  = $receipt;
        $rec->amount         = $tx->amount;
        $rec->type           = $tx->type;
        $rec->phone          = $tx->phone;
        $rec->reconciled     = true;
        $rec->reconciled_at  = time();
        $rec->created_at     = time();
        $rec->save(false);
    }

    private function findTransaction(?string $convId, ?string $origConvId): ?MpesaTransactions
    {
        if ($convId) {
            $tx = MpesaTransactions::find()->where(['conversation_id' => $convId])->one();
            if ($tx) {
                return $tx;
            }
        }
        if ($origConvId) {
            return MpesaTransactions::find()->where(['originator_conversation_id' => $origConvId])->one();
        }
        return null;
    }

    private function extractResultParams(array $params): array
    {
        $meta = [];
        foreach ($params as $p) {
            $meta[$p['Key']] = $p['Value'] ?? null;
        }
        return $meta;
    }

    private function handleApiError(string $type, array $result): array
    {
        $resultCode = $result['errorCode'] ?? $result['ResultCode'] ?? -1;
        $resultDesc = $result['errorMessage'] ?? $result['ResultDesc'] ?? $result['exception'] ?? 'Unknown error';

        $this->saveFailure(null, $type, (int) $resultCode, mb_substr((string) $resultDesc, 0, 255), $result);

        return [
            'success' => false,
            'error'   => $resultDesc,
            'data'    => $result,
        ];
    }
}