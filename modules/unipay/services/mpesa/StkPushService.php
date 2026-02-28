<?php

namespace unipay\services\mpesa;

use Yii;
use unipay\models\MpesaTransaction;
use unipay\hooks\BaseModel;

/**
 * Lipa Na M-Pesa Online (STK Push)
 * Prompts the customer's phone to enter their M-Pesa PIN.
 */
class StkPushService extends MpesaClient
{
    /**
     * Initiate an STK Push request.
     *
     * @param  float  $amount        Amount in KES
     * @param  string $phone         Customer phone: 254XXXXXXXXX
     * @param  string $accountRef    Account reference shown on customer phone (max 12 chars)
     * @param  string $description   Transaction description (max 13 chars)
     * @return array  Safaricom response including CheckoutRequestID
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public function push(
        float $amount,
        string $phone,
        string $accountRef = 'Payment',
        string $description = 'Payment'
    ): array {
        $this->validateRequiredEnv(['MPESA_SHORTCODE', 'MPESA_PASSKEY']);

        $phone = $this->normalisePhone($phone);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('[unipay] StkPushService: amount must be > 0.');
        }

        $timestamp = date('YmdHis');
        $password  = base64_encode(
            $_SERVER['MPESA_SHORTCODE'] . $_SERVER['MPESA_PASSKEY'] . $timestamp
        );

        // Create pending record
        $transaction         = new MpesaTransaction();
        $transaction->type   = MpesaTransaction::TYPE_C2B;
        $transaction->amount = $amount;
        $transaction->phone  = $phone;
        $transaction->status = MpesaTransaction::STATUS_PENDING;
        $transaction->created_at = time();
        $transaction->updated_at = time();
        $transaction->save();

        Yii::info("[unipay] STK Push: KES {$amount} from {$phone}", __METHOD__);

        return $this->request('/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $_SERVER['MPESA_SHORTCODE'],
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int)$amount,
            'PartyA'            => $phone,
            'PartyB'            => $_SERVER['MPESA_SHORTCODE'],
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $this->callbackUrl('unipay/mpesa/stk-callback'),
            'AccountReference'  => substr($accountRef, 0, 12),
            'TransactionDesc'   => substr($description, 0, 13),
        ]);
    }

    /**
     * Query the status of a previously submitted STK Push.
     *
     * @param  string $checkoutRequestId  Returned by push()
     * @return array
     */
    public function query(string $checkoutRequestId): array
    {
        $this->validateRequiredEnv(['MPESA_SHORTCODE', 'MPESA_PASSKEY']);

        $timestamp = date('YmdHis');
        $password  = base64_encode(
            $_SERVER['MPESA_SHORTCODE'] . $_SERVER['MPESA_PASSKEY'] . $timestamp
        );

        return $this->request('/mpesa/stkpushquery/v1/query', [
            'BusinessShortCode' => $_SERVER['MPESA_SHORTCODE'],
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ]);
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
                throw new \RuntimeException("[unipay] StkPushService: env var {$key} is not set.");
            }
        }
    }
}