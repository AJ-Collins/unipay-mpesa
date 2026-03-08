<?php

namespace unipay\controllers;

use Yii;
use unipay\models\MpesaTransactions;
use unipay\models\MpesaWebhookLogs;
use unipay\services\mpesa\MpesaTransactionService;

/**
 * @OA\Tag(
 *     name="M-Pesa",
 *     description="Safaricom M-Pesa payment integrations"
 * )
 */
class MpesaController extends \helpers\Controller
{
    public $accessLog = false;

    private MpesaTransactionService $mpesa;

    public function init()
    {
        parent::init();
        $this->mpesa = new MpesaTransactionService();
    }

    public function actionStkPush()
    {
        $params = Yii::$app->request->getBodyParams();

        // Basic input validation
        $model = new \yii\base\DynamicModel(['phone', 'amount', 'account_ref']);
        $model->addRule(['phone', 'amount', 'account_ref'], 'required')
            ->addRule(['amount'], 'number', ['min' => 1]);
        $model->load(['DynamicModel' => $params]);

        if (!$model->validate()) {
            return $this->errorResponse($model->getErrors());
        }
        
        $userId = Yii::$app->user->id ?? null;
        $result = $this->mpesa->stkPush($params, $userId);

        if (!$result['success']) {
            return $this->errorResponse(400, false, $result['error'] ?? 'STK Push failed');
        }

        return $this->payloadResponse($result['data'], [
            'statusCode' => 200,
            'message'    => 'Payment request sent. Please check your phone and enter your M-Pesa PIN.',
            'type'       => 'toast',
            'theme'      => 'success',
        ]);
    }

    public function actionStkQuery()
    {
        $params = Yii::$app->request->getBodyParams();

        if (empty($params['checkout_request_id'])) {
            return $this->errorResponse(['checkout_request_id' => ['CheckoutRequestID is required']]);
        }

        $result = $this->mpesa->stkQuery($params['checkout_request_id']);
        return $this->payloadResponse($result['data'], ['statusCode' => 200]);
    }

    public function actionC2bRegister()
    {
        $params       = Yii::$app->request->getBodyParams();
        $responseType = $params['response_type'] ?? 'Completed';

        $result = $this->mpesa->c2bRegisterUrls($responseType);

        return $this->payloadResponse($result['data'], [
            'statusCode' => 200,
            'message'    => 'C2B URLs registered successfully.',
            'type'       => 'toast',
            'theme'      => 'success',
        ]);
    }

    public function actionC2bSimulate()
    {
        $params = Yii::$app->request->getBodyParams();

        if (empty($params['phone']) || empty($params['amount'])) {
            return $this->errorResponse(['phone' => ['Phone is required'], 'amount' => ['Amount is required']]);
        }

        $result = $this->mpesa->c2bSimulate($params);
        return $this->payloadResponse($result['data'], ['statusCode' => 200]);
    }

    public function actionB2c()
    {
        $params = Yii::$app->request->getBodyParams();

        if (empty($params['phone']) || empty($params['amount'])) {
            return $this->errorResponse(['phone' => ['Phone is required'], 'amount' => ['Amount is required']]);
        }

        if ((float) $params['amount'] < 1) {
            return $this->errorResponse(['amount' => ['Minimum amount is KES 1']]);
        }

        $userId = Yii::$app->user->id ?? null;
        $result = $this->mpesa->b2c($params, $userId);

        if (!$result['success']) {
            return $this->errorResponse(400, false, $result['error'] ?? 'B2C request failed');
        }

        return $this->payloadResponse($result['data'], [
            'statusCode' => 200,
            'message'    => 'B2C payment initiated successfully.',
            'type'       => 'toast',
            'theme'      => 'success',
        ]);
    }

    public function actionB2b()
    {
        $params = Yii::$app->request->getBodyParams();

        if (empty($params['receiver_shortcode']) || empty($params['amount'])) {
            return $this->errorResponse(['receiver_shortcode' => ['Receiver shortcode is required'], 'amount' => ['Amount is required']]);
        }

        $userId = Yii::$app->user->id ?? null;
        $result = $this->mpesa->b2b($params, $userId);

        if (!$result['success']) {
            return $this->errorResponse(400, false, $result['error'] ?? 'B2B request failed');
        }

        return $this->payloadResponse($result['data'], [
            'statusCode' => 200,
            'message'    => 'B2B payment initiated successfully.',
            'type'       => 'toast',
            'theme'      => 'success',
        ]);
    }

    public function actionTransactionStatus()
    {
        $params = Yii::$app->request->getBodyParams();

        if (empty($params['transaction_id'])) {
            return $this->errorResponse(['transaction_id' => ['Transaction ID (M-Pesa receipt) is required']]);
        }

        $result = $this->mpesa->transactionStatus($params);
        return $this->payloadResponse($result['data'], ['statusCode' => 200]);
    }

    public function actionAccountBalance()
    {
        $result = $this->mpesa->accountBalance();
        return $this->payloadResponse($result['data'], ['statusCode' => 200]);
    }

    public function actionReversal()
    {
        $params = Yii::$app->request->getBodyParams();

        if (empty($params['transaction_id']) || empty($params['amount'])) {
            return $this->errorResponse([
                'transaction_id' => ['Transaction ID is required'],
                'amount'         => ['Amount is required'],
            ]);
        }

        $result = $this->mpesa->reversal($params);
        if (!$result['success']) {
            return $this->errorResponse(400, false, $result['error'] ?? 'Reversal failed');
        }

        return $this->payloadResponse($result['data'], [
            'statusCode' => 200,
            'message'    => 'Reversal request submitted successfully.',
            'type'       => 'toast',
            'theme'      => 'success',
        ]);
    }

    public function actionTransactions()
    {
        $userId  = Yii::$app->user->id;
        $request = Yii::$app->request;

        $query = MpesaTransactions::find()->orderBy(['created_at' => SORT_DESC]);

        // Non-admin users see only their own transactions
        $roles = array_keys(Yii::$app->authManager->getRolesByUser($userId));
        if (!in_array('admin', $roles, true) && !in_array('superadmin', $roles, true)) {
            $query->andWhere(['user_id' => $userId]);
        }

        if ($type = $request->get('type')) {
            $query->andWhere(['type' => strtoupper($type)]);
        }

        if ($status = $request->get('status')) {
            $query->andWhere(['status' => strtoupper($status)]);
        }

        $provider = new \yii\data\ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->payloadResponse($provider, ['oneRecord' => false]);
    }

    public function actionTransaction(int $id)
    {
        $tx = MpesaTransactions::find()
            ->with(['mpesaResponses', 'mpesaReconciliations'])
            ->where(['id' => $id])
            ->one();

        if (!$tx) {
            return $this->errorResponse(404);
        }

        // Authorize: admin or owner
        $roles = array_keys(Yii::$app->authManager->getRolesByUser(Yii::$app->user->id));
        $isAdmin = in_array('admin', $roles, true) || in_array('superadmin', $roles, true);
        if (!$isAdmin && $tx->user_id !== Yii::$app->user->id) {
            return $this->errorResponse(403);
        }

        return $this->payloadResponse($tx, ['statusCode' => 200]);
    }

    /**
     * STK Push callback
     */
    public function actionStkCallback()
    {
        $payload = $this->getCallbackPayload('stk-callback');
        if ($payload === null) {
            return $this->safaricomAck();
        }

        $this->mpesa->processStkCallback($payload);
        return $this->safaricomAck();
    }

    /**
     * C2B Validation callback
     * Return ResultCode 0 to accept, 1 to reject
     */
    public function actionC2bValidation()
    {
        $payload = $this->getCallbackPayload('c2b-validation');

        // Always accept in this implementation — add business rules here if needed
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
    }

    /**
     * C2B Confirmation callback
     */
    public function actionC2bConfirmation()
    {
        $payload = $this->getCallbackPayload('c2b-confirmation');
        if ($payload === null) {
            return $this->safaricomAck();
        }

        $this->mpesa->processC2bConfirmation($payload);
        return $this->safaricomAck();
    }

    /**
     * B2C Result callback
     */
    public function actionB2cResult()
    {
        $payload = $this->getCallbackPayload('b2c-result');
        if ($payload === null) {
            return $this->safaricomAck();
        }

        $this->mpesa->processB2cResult($payload);
        return $this->safaricomAck();
    }

    /**
     * B2C Timeout callback
     */
    public function actionB2cTimeout()
    {
        $payload = $this->getCallbackPayload('b2c-timeout');
        if ($payload) {
            $this->mpesa->processTimeout($payload, 'B2C');
        }
        return $this->safaricomAck();
    }

    /**
     * B2B Result callback
     */
    public function actionB2bResult()
    {
        $payload = $this->getCallbackPayload('b2b-result');
        if ($payload === null) {
            return $this->safaricomAck();
        }

        $this->mpesa->processB2bResult($payload);
        return $this->safaricomAck();
    }

    /**
     * B2B Timeout callback
     */
    public function actionB2bTimeout()
    {
        $payload = $this->getCallbackPayload('b2b-timeout');
        if ($payload) {
            $this->mpesa->processTimeout($payload, 'B2B');
        }
        return $this->safaricomAck();
    }

    /**
     * Transaction Status Result callback
     */
    public function actionStatusResult()
    {
        $this->getCallbackPayload('status-result');
        return $this->safaricomAck();
    }

    /**
     * Transaction Status Timeout callback
     */
    public function actionStatusTimeout()
    {
        $this->getCallbackPayload('status-timeout');
        return $this->safaricomAck();
    }

    /**
     * Account Balance Result callback
     */
    public function actionBalanceResult()
    {
        $this->getCallbackPayload('balance-result');
        return $this->safaricomAck();
    }

    /**
     * Account Balance Timeout callback
     */
    public function actionBalanceTimeout()
    {
        $this->getCallbackPayload('balance-timeout');
        return $this->safaricomAck();
    }

    /**
     * Reversal Result callback
     */
    public function actionReversalResult()
    {
        $payload = $this->getCallbackPayload('reversal-result');
        if ($payload) {
            $result     = $payload['Result'] ?? [];
            $resultCode = $result['ResultCode'] ?? -1;
            $convId     = $result['ConversationID'] ?? null;

            if ($convId) {
                $tx = \unipay\models\MpesaTransactions::find()
                    ->where(['conversation_id' => $convId])
                    ->one();
                if ($tx) {
                    $tx->status = $resultCode == 0 ? 'REVERSED' : 'FAILED';
                    $tx->save(false);
                }
            }
        }
        return $this->safaricomAck();
    }

    /**
     * Reversal Timeout callback
     */
    public function actionReversalTimeout()
    {
        $this->getCallbackPayload('reversal-timeout');
        return $this->safaricomAck();
    }

    /**
     * Read, log and return raw callback payload.
     * Returns null if IP is not whitelisted.
     */
    private function getCallbackPayload(string $endpoint): ?array
    {
        $ip      = Yii::$app->request->userIP;
        $raw     = Yii::$app->request->rawBody;
        $payload = json_decode($raw, true) ?? [];

        $mpesaService = new \unipay\services\mpesa\MpesaService();
        $ipOk = $mpesaService->isSafaricomIp($ip);

        // Always log the webhook
        $log               = new MpesaWebhookLogs();
        $log->endpoint     = $endpoint;
        $log->ip_address   = $ip;
        $log->raw_payload  = $payload;
        $log->safaricom_ip_ok = $ipOk;
        $log->processed    = false;
        $log->save(false);

        if (!$ipOk) {
            Yii::warning("[Callback] Rejected IP: $ip for endpoint: $endpoint", 'mpesa');
            return null;
        }

        // Mark as processed
        $log->processed = true;
        $log->save(false);

        return $payload;
    }

    /**
     * Standard Safaricom acknowledgement response
     */
    private function safaricomAck(): array
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['ResultCode' => 0, 'ResultDesc' => 'Success'];
    }
}