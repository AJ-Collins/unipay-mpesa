<?php

namespace unipay\services\mpesa;

use Yii;
use unipay\hooks\BaseModel;

class C2BService extends MpesaClient
{
    /**
     * Register Confirmation & Validation URLs with Safaricom.
     * Must be called once (or whenever the URLs change).
     *
     * @return array Safaricom response
     * @throws \RuntimeException
     */
    public function registerUrls(): array
    {
        $shortCode = $_SERVER['MPESA_SHORTCODE'] ?? null;

        if (!$shortCode) {
            throw new \RuntimeException('[unipay] C2BService: MPESA_SHORTCODE is not set.');
        }

        Yii::info('[unipay] C2B: registering callback URLs for shortcode ' . $shortCode, __METHOD__);

        return $this->request('/mpesa/c2b/v2/registerurl', [
            'ShortCode'       => $shortCode,
            'ResponseType'    => 'Completed',
            'ConfirmationURL' => $this->callbackUrl('unipay/mpesa/c2b-confirm'),
            'ValidationURL'   => $this->callbackUrl('unipay/mpesa/c2b-validate'),
        ]);
    }

    /**
     * Simulate a C2B payment (sandbox only).
     *
     * @param  string $phone      Payer phone (254XXXXXXXXX)
     * @param  float  $amount
     * @param  string $billRefNo  Account reference / bill number
     * @return array
     * @throws \RuntimeException
     */
    public function simulate(string $phone, float $amount, string $billRefNo = 'TestRef'): array
    {
        $shortCode = $_SERVER['MPESA_SHORTCODE'] ?? null;

        if (!$shortCode) {
            throw new \RuntimeException('[unipay] C2BService: MPESA_SHORTCODE is not set.');
        }

        $phone = $this->normalisePhone($phone);

        return $this->request('/mpesa/c2b/v2/simulate', [
            'ShortCode'     => $shortCode,
            'CommandID'     => 'CustomerPayBillOnline',
            'Amount'        => (int)$amount,
            'Msisdn'        => $phone,
            'BillRefNumber' => $billRefNo,
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
}