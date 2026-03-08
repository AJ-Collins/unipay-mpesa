<?php

namespace unipay\services\mpesa;

use Yii;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/*
 * Handles:
 * - OAuth token generation & caching
 * - STK Push (C2B Lipa na M-Pesa Online)
 * - C2B Register URLs
 * - B2C (Business to Customer)
 * - B2B (Business to Business)
 * - Transaction Status Query
 * - Account Balance Query
 * - Reversal
 * - Phone number formatting
 */
class MpesaService
{
    private Client $client;
    private string $baseUrl;
    private string $consumerKey;
    private string $consumerSecret;
    private string $shortcode;
    private string $passkey;
    private string $initiator;
    private string $securityCredential;
    private string $callbackBase;
    private bool   $isSandbox;

    const SAFARICOM_IPS = [
        '196.201.214.200', '196.201.214.206', '196.201.213.114',
        '196.201.214.207', '196.201.214.208', '196.201.213.44',
        '196.201.212.127', '196.201.212.138', '196.201.212.129',
        '196.201.212.136', '196.201.212.74',  '196.201.212.69',
    ];

    public function __construct()
    {
        $this->isSandbox          = ($_SERVER['MPESA_ENV']                ?? 'sandbox') === 'sandbox';
        $this->baseUrl            = rtrim($_SERVER['MPESA_BASE_URL']       ?? 'https://sandbox.safaricom.co.ke', '/');
        $this->consumerKey        = $_SERVER['MPESA_CONSUMER_KEY']         ?? '';
        $this->consumerSecret     = $_SERVER['MPESA_CONSUMER_SECRET']      ?? '';
        $this->shortcode          = $_SERVER['MPESA_SHORTCODE']            ?? '';
        $this->passkey            = $_SERVER['MPESA_PASSKEY']              ?? '';
        $this->initiator          = $_SERVER['MPESA_INITIATOR']            ?? '';
        $this->securityCredential = $_SERVER['MPESA_SECURITY_CREDENTIAL']  ?? '';
        $this->callbackBase       = rtrim($_SERVER['MPESA_CALLBACK_BASE']  ?? 'https://yourdomain.com', '/');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
            'headers'  => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        ]);
    }

    /**
     * Normalize any Kenyan number to 254XXXXXXXXX (12 digits)
     * Handles: +254..., 254..., 07..., 01...
     *
     * @throws \InvalidArgumentException on invalid format
     */
    public function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);

        if (preg_match('/^\+?(254)(7\d{8}|1[01]\d{7})$/', $phone, $m)) {
            return '254' . $m[2];
        }

        if (preg_match('/^0(7\d{8}|1[01]\d{7})$/', $phone, $m)) {
            return '254' . $m[1];
        }

        throw new \InvalidArgumentException("Invalid Kenyan phone number: $phone");
    }

    /**
     * Get access token (cached in Yii cache for 55 minutes)
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'mpesa_access_token';
        $cached   = Yii::$app->cache->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $credentials = base64_encode("{$this->consumerKey}:{$this->consumerSecret}");

        $response = $this->client->get('/oauth/v1/generate?grant_type=client_credentials', [
            'headers' => ['Authorization' => "Basic $credentials"],
        ]);

        $data  = json_decode($response->getBody()->getContents(), true);
        $token = $data['access_token'];

        // Cache for 55 min (token expires in 60)
        Yii::$app->cache->set($cacheKey, $token, 3300);

        return $token;
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer ' . $this->getAccessToken()];
    }

    private function timestamp(): string
    {
        return date('YmdHis');
    }

    private function password(): string
    {
        return base64_encode($this->shortcode . $this->passkey . $this->timestamp());
    }

    private function callbackUrl(string $path): string
    {
        return "{$this->callbackBase}/unipay/mpesa/{$path}";
    }

    /**
     * Low-level POST to Safaricom API
     */
    private function post(string $endpoint, array $payload): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'headers' => $this->authHeader(),
                'json'    => $payload,
            ]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (RequestException $e) {
            $body = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : [];
            Yii::error('[MpesaService] POST ' . $endpoint . ' failed: ' . json_encode($body), 'mpesa');
            return array_merge(['error' => true, 'exception' => $e->getMessage()], $body ?? []);
        }
    }

    /**
     * Initiate STK Push (customer prompted on phone)
     *
     * @param string $phone   Raw phone number (any format)
     * @param float  $amount
     * @param string $accountRef  e.g. order ID
     * @param string $description  Transaction description (max 20 chars shown)
     */
    public function stkPush(string $phone, float $amount, string $accountRef, string $description = 'Payment'): array
    {
        $ts    = $this->timestamp();
        $phone = $this->formatPhone($phone);

        return $this->post('/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $this->shortcode,
            'Password'          => base64_encode($this->shortcode . $this->passkey . $ts),
            'Timestamp'         => $ts,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int) ceil($amount),
            'PartyA'            => $phone,
            'PartyB'            => $this->shortcode,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $this->callbackUrl('stk-callback'),
            'AccountReference'  => substr($accountRef, 0, 12),
            'TransactionDesc'   => substr($description, 0, 20),
        ]);
    }

    /**
     * Query STK Push transaction status
     */
    public function stkQuery(string $checkoutRequestId): array
    {
        $ts = $this->timestamp();
        return $this->post('/mpesa/stkpushquery/v1/query', [
            'BusinessShortCode' => $this->shortcode,
            'Password'          => base64_encode($this->shortcode . $this->passkey . $ts),
            'Timestamp'         => $ts,
            'CheckoutRequestID' => $checkoutRequestId,
        ]);
    }

    /**
     * Register C2B callback URLs (one-time setup or re-register)
     *
     * @param string $responseType  'Completed' or 'Cancelled'
     */
    public function c2bRegisterUrls(string $responseType = 'Completed'): array
    {
        return $this->post('/mpesa/c2b/v1/registerurl', [
            'ShortCode'       => $this->shortcode,
            'ResponseType'    => $responseType,
            'ConfirmationURL' => $this->callbackUrl('c2b-confirmation'),
            'ValidationURL'   => $this->callbackUrl('c2b-validation'),
        ]);
    }

    /**
     * Simulate C2B payment (sandbox only)
     */
    public function c2bSimulate(string $phone, float $amount, string $billRef = 'TEST'): array
    {
        return $this->post('/mpesa/c2b/v1/simulate', [
            'ShortCode'     => $this->shortcode,
            'CommandID'     => 'CustomerPayBillOnline',
            'Amount'        => (int) ceil($amount),
            'Msisdn'        => $this->formatPhone($phone),
            'BillRefNumber' => $billRef,
        ]);
    }

    /**
     * Send money to customer
     *
     * CommandID options:
     *   SalaryPayment     – Salary disbursements
     *   BusinessPayment   – e.g. ad-hoc business payments
     *   PromotionPayment  – Promotional disbursements
     */
    public function b2c(
        string $phone,
        float  $amount,
        string $commandId = 'BusinessPayment',
        string $remarks   = 'Payment',
        string $occasion  = ''
    ): array {
        return $this->post('/mpesa/b2c/v1/paymentrequest', [
            'InitiatorName'      => $this->initiator,
            'SecurityCredential' => $this->securityCredential,
            'CommandID'          => $commandId,
            'Amount'             => (int) ceil($amount),
            'PartyA'             => $this->shortcode,
            'PartyB'             => $this->formatPhone($phone),
            'Remarks'            => substr($remarks, 0, 100),
            'QueueTimeOutURL'    => $this->callbackUrl('b2c-timeout'),
            'ResultURL'          => $this->callbackUrl('b2c-result'),
            'Occasion'           => substr($occasion, 0, 100),
        ]);
    }

    /**
     * Send money to another business / paybill / till
     *
     * CommandID options:
     *   BusinessPayBill, MerchantToMerchantTransfer, MerchantTransferFromMerchantToWorking,
     *   MerchantServicesMMFAccountTransfer, AgencyFloatAdvance
     */
    public function b2b(
        string $receiverShortcode,
        float  $amount,
        string $commandId     = 'BusinessPayBill',
        string $senderIdType  = '4',
        string $receiverIdType = '4',
        string $remarks       = 'B2B Payment',
        string $accountRef    = ''
    ): array {
        return $this->post('/mpesa/b2b/v1/paymentrequest', [
            'Initiator'              => $this->initiator,
            'SecurityCredential'     => $this->securityCredential,
            'CommandID'              => $commandId,
            'SenderIdentifierType'   => $senderIdType,
            'RecieverIdentifierType' => $receiverIdType,
            'Amount'                 => (int) ceil($amount),
            'PartyA'                 => $this->shortcode,
            'PartyB'                 => $receiverShortcode,
            'AccountReference'       => substr($accountRef ?: $receiverShortcode, 0, 12),
            'Remarks'                => substr($remarks, 0, 100),
            'QueueTimeOutURL'        => $this->callbackUrl('b2b-timeout'),
            'ResultURL'              => $this->callbackUrl('b2b-result'),
        ]);
    }

    /**
     * Query the status of any M-Pesa transaction
     *
     * @param string $transactionId  M-Pesa receipt number (e.g. OEI2AK4DKL)
     * @param string $identifierType '1'=MSISDN, '2'=Till, '4'=Shortcode
     */
    public function transactionStatus(
        string $transactionId,
        string $identifierType = '4',
        string $remarks        = 'Status query',
        string $occasion       = ''
    ): array {
        return $this->post('/mpesa/transactionstatus/v1/query', [
            'Initiator'          => $this->initiator,
            'SecurityCredential' => $this->securityCredential,
            'CommandID'          => 'TransactionStatusQuery',
            'TransactionID'      => $transactionId,
            'PartyA'             => $this->shortcode,
            'IdentifierType'     => $identifierType,
            'ResultURL'          => $this->callbackUrl('status-result'),
            'QueueTimeOutURL'    => $this->callbackUrl('status-timeout'),
            'Remarks'            => substr($remarks, 0, 100),
            'Occasion'           => substr($occasion, 0, 100),
        ]);
    }

    /**
     * Query account balance for shortcode
     *
     * @param string $identifierType '4' = Organization Shortcode
     */
    public function accountBalance(string $identifierType = '4', string $remarks = 'Balance query'): array
    {
        return $this->post('/mpesa/accountbalance/v1/query', [
            'Initiator'          => $this->initiator,
            'SecurityCredential' => $this->securityCredential,
            'CommandID'          => 'AccountBalance',
            'PartyA'             => $this->shortcode,
            'IdentifierType'     => $identifierType,
            'Remarks'            => substr($remarks, 0, 100),
            'QueueTimeOutURL'    => $this->callbackUrl('balance-timeout'),
            'ResultURL'          => $this->callbackUrl('balance-result'),
        ]);
    }

    /**
     * Reverse a transaction
     *
     * @param string $transactionId  M-Pesa receipt to reverse
     * @param float  $amount         Must match original transaction
     * @param string $receiverIdType '11' = MSISDN for C2B reversals
     */
    public function reversal(
        string $transactionId,
        float  $amount,
        string $receiverIdType = '11',
        string $remarks        = 'Reversal',
        string $occasion       = ''
    ): array {
        return $this->post('/mpesa/reversal/v1/request', [
            'Initiator'              => $this->initiator,
            'SecurityCredential'     => $this->securityCredential,
            'CommandID'              => 'TransactionReversal',
            'TransactionID'          => $transactionId,
            'Amount'                 => (int) ceil($amount),
            'ReceiverParty'          => $this->shortcode,
            'RecieverIdentifierType' => $receiverIdType,
            'ResultURL'              => $this->callbackUrl('reversal-result'),
            'QueueTimeOutURL'        => $this->callbackUrl('reversal-timeout'),
            'Remarks'                => substr($remarks, 0, 100),
            'Occasion'               => substr($occasion, 0, 100),
        ]);
    }

    /**
     * Check whether a given IP is from Safaricom's known callback IPs.
     * In sandbox mode, always returns true.
     */
    public function isSafaricomIp(string $ip): bool
    {
        if ($this->isSandbox) {
            return true;
        }
        return in_array($ip, self::SAFARICOM_IPS, true);
    }
}