<?php

namespace unipay\controllers;

use Yii;
use yii\web\Response;
use unipay\hooks\BaseModel;
use unipay\models\MpesaTransaction;

class MpesaController extends \helpers\Controller
{
    public $enableCsrfValidation = false;

    public function init()
    {
        parent::init();
        Yii::$app->response->format = Response::FORMAT_JSON;
    }
    /**
     * C2B  (Customer → Business  |  Paybill / Till payments)
     * Safaricom hits this URL to validate a payment before processing it.
     * Return ResultCode 0 to accept, anything else to reject.
     */
    public function actionC2bValidate()
    {
        $data = $this->getJsonInput();
        Yii::info('[unipay] C2B Validate: ' . json_encode($data), __METHOD__);

        // Custom validation logic
        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
    }

    /**
     * Safaricom confirms the payment was processed successfully.
     */
    public function actionC2bConfirm()
    {
        $data = $this->getJsonInput();

        if (empty($data)) {
            Yii::warning('[unipay] C2B Confirm: empty payload', __METHOD__);
            return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
        }

        $transaction = BaseModel::logMpesaTransaction('C2B', $data);

        if ($transaction === null) {
            Yii::error('[unipay] C2B Confirm: failed to log transaction', __METHOD__);
        }

        // Always return 0 to Safaricom to avoid retry
        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
    }

    /**
     * B2C  (Business → Customer  |  Disbursements / Salaries / Refunds
     * Safaricom posts the B2C result here after processing.
     */
    public function actionB2cResult()
    {
        $content = $this->getJsonInput();

        if (!isset($content['Result'])) {
            Yii::warning('[unipay] B2C Result: missing Result key', __METHOD__);
            return ['ResultCode' => 0];
        }

        $result = $content['Result'];
        $conversationId = $result['ConversationID'] ?? null;

        // Flatten ResultParameters into the result array for easier handling
        $result = array_merge($result, $this->flattenResultParams($result));

        $transaction = BaseModel::logMpesaTransaction('B2C', $result, $conversationId);

        if ($transaction === null) {
            Yii::error('[unipay] B2C Result: failed to log transaction', __METHOD__);
        }

        return ['ResultCode' => 0];
    }

    /**
     * Safaricom hits this URL when the B2C request times out.
     */
    public function actionB2cTimeout()
    {
        $content = $this->getJsonInput();
        Yii::warning('[unipay] B2C Timeout: ' . json_encode($content), __METHOD__);

        $conversationId = $content['Result']['ConversationID'] ?? null;

        if ($conversationId) {
            MpesaTransaction::updateAll(
                ['status' => 'TIMEOUT', 'updated_at' => time()],
                ['conversation_id' => $conversationId]
            );
        }

        return ['ResultCode' => 0];
    }

    /**
     * B2B  (Business → Business  |  Paybill / Till transfers)
     * Safaricom posts the B2B result here after processing.
     */
    public function actionB2bResult()
    {
        $content = $this->getJsonInput();

        if (!isset($content['Result'])) {
            Yii::warning('[unipay] B2B Result: missing Result key', __METHOD__);
            return ['ResultCode' => 0];
        }

        $result = $content['Result'];
        $conversationId = $result['ConversationID'] ?? null;

        $result = array_merge($result, $this->flattenResultParams($result));

        $transaction = BaseModel::logMpesaTransaction('B2B', $result, $conversationId);

        if ($transaction === null) {
            Yii::error('[unipay] B2B Result: failed to log transaction', __METHOD__);
        }

        return ['ResultCode' => 0];
    }

    /**
     * Safaricom hits this URL when the B2B request times out.
     */
    public function actionB2bTimeout()
    {
        $content = $this->getJsonInput();
        Yii::warning('[unipay] B2B Timeout: ' . json_encode($content), __METHOD__);

        $conversationId = $content['Result']['ConversationID'] ?? null;

        if ($conversationId) {
            MpesaTransaction::updateAll(
                ['status' => 'TIMEOUT', 'updated_at' => time()],
                ['conversation_id' => $conversationId]
            );
        }

        return ['ResultCode' => 0];
    }

    /**
     * STK Push  (Lipa Na M-Pesa Online  |  Customer-initiated prompt)
     * Safaricom posts the STK Push callback here.
     */
    public function actionStkCallback()
    {
        $content = $this->getJsonInput();

        $body     = $content['Body']['stkCallback'] ?? null;

        if (!$body) {
            Yii::warning('[unipay] STK Callback: malformed payload', __METHOD__);
            return ['ResultCode' => 0];
        }

        $resultCode = (int)($body['ResultCode'] ?? -1);
        $data = [
            'ResultCode'    => $resultCode,
            'ResultDesc'    => $body['ResultDesc'] ?? null,
            'MerchantRequestID'   => $body['MerchantRequestID'] ?? null,
            'CheckoutRequestID'   => $body['CheckoutRequestID'] ?? null,
            'CallbackMetadata'    => $body['CallbackMetadata'] ?? [],
        ];

        // Extract individual metadata items
        if ($resultCode === 0 && !empty($data['CallbackMetadata']['Item'])) {
            foreach ($data['CallbackMetadata']['Item'] as $item) {
                $data[$item['Name']] = $item['Value'] ?? null;
            }
        }

        $transaction = BaseModel::logMpesaTransaction('C2B', $data);

        if ($transaction === null) {
            Yii::error('[unipay] STK Callback: failed to log transaction', __METHOD__);
        }

        return ['ResultCode' => 0];
    }

    /**
     * Helpers
     * Safely read & decode JSON from php://input.
     */
    private function getJsonInput(): array
    {
        try {
            $raw = file_get_contents('php://input');
            if (empty($raw)) {
                return [];
            }
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Yii::warning('[unipay] JSON decode error: ' . json_last_error_msg(), __METHOD__);
                return [];
            }
            return $decoded ?? [];
        } catch (\Throwable $e) {
            Yii::error('[unipay] getJsonInput error: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }

    /**
     * Flatten Safaricom's ResultParameters.ResultParameter array into a
     * simple key→value array so BaseModel can handle it uniformly.
     */
    private function flattenResultParams(array $result): array
    {
        $flat = [];
        $items = $result['ResultParameters']['ResultParameter'] ?? [];
        foreach ($items as $item) {
            if (isset($item['Key'])) {
                $flat[$item['Key']] = $item['Value'] ?? null;
            }
        }
        return $flat;
    }
}