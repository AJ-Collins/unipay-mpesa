<?php
return [

    /**
     * @OA\Post(
     *   path="/unipay/mpesa/stk-push",
     *   summary="STK Push",
     *   description="Prompt customer to pay via M-Pesa on their phone (Lipa Na M-Pesa Online).",
     *   tags={"M-Pesa"},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"phone","amount","account_ref"},
     *     @OA\Property(property="phone",       type="string", example="0712345678"),
     *     @OA\Property(property="amount",      type="number", example=100),
     *     @OA\Property(property="account_ref", type="string", example="INV-001"),
     *     @OA\Property(property="description", type="string", example="Order payment")
     *   )),
     *   @OA\Response(response=200, description="STK Push initiated",
     *     @OA\JsonContent(@OA\Property(property="dataPayload", type="object",
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="CheckoutRequestID", type="string"),
     *         @OA\Property(property="MerchantRequestID", type="string"),
     *         @OA\Property(property="ResponseCode",      type="string"),
     *         @OA\Property(property="ResponseDescription", type="string"),
     *         @OA\Property(property="CustomerMessage",   type="string")
     *       )
     *     ))
     *   ),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    'POST mpesa/stk-push' => 'mpesa/stk-push',

    /**
     * @OA\Post(
     *   path="/unipay/mpesa/stk-query",
     *   summary="STK Push Query",
     *   description="Check the status of a pending STK Push transaction.",
     *   tags={"M-Pesa"},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"checkout_request_id"},
     *     @OA\Property(property="checkout_request_id", type="string", example="ws_CO_260520211133524545")
     *   )),
     *   @OA\Response(response=200, description="Query result")
     * )
     */
    'POST mpesa/stk-query' => 'mpesa/stk-query',

    /**
     * @OA\Post(
     *   path="/unipay/mpesa/c2b-register",
     *   summary="Register C2B URLs",
     *   description="Register Validation and Confirmation URLs for paybill/till C2B payments.",
     *   tags={"M-Pesa"},
     *   @OA\RequestBody(@OA\JsonContent(
     *     @OA\Property(property="response_type", type="string", example="Completed")
     *   )),
     *   @OA\Response(response=200, description="URLs registered successfully")
     * )
     */
    'POST mpesa/c2b-register' => 'mpesa/c2b-register',

    /**
     * @OA\Post(
     *   path="/unipay/mpesa/c2b-simulate",
     *   summary="Simulate C2B (Sandbox only)",
     *   description="Simulate a customer-to-business payment in sandbox environment.",
     *   tags={"M-Pesa"},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"phone","amount"},
     *     @OA\Property(property="phone",    type="string", example="254712345678"),
     *     @OA\Property(property="amount",   type="number", example=10),
     *     @OA\Property(property="bill_ref", type="string", example="TEST123")
     *   )),
     *   @OA\Response(response=200, description="Simulation accepted")
     * )
     */
    'POST mpesa/c2b-simulate' => 'mpesa/c2b-simulate',

    /**
     * @OA\Post(
     *   path="/unipay/mpesa/b2c",
     *   summary="B2C Payment",
     *   description="Send money from business shortcode to a customer M-Pesa wallet (salary, refunds, promotions).",
     *   tags={"M-Pesa"},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"phone","amount"},
     *     @OA\Property(property="phone",      type="string", example="0712345678"),
     *     @OA\Property(property="amount",     type="number", example=500),
     *     @OA\Property(property="command_id", type="string", example="BusinessPayment"),
     *     @OA\Property(property="remarks",    type="string", example="Refund"),
     *     @OA\Property(property="occasion",   type="string", example="")
     *   )),
     *   @OA\Response(response=200, description="B2C initiated"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    'POST mpesa/b2c' => 'mpesa/b2c',

    /**
     * @OA\Post(
     *   path="/unipay/mpesa/b2b",
     *   summary="B2B Payment",
     *   description="Send money to another business paybill or till number.",
     *   tags={"M-Pesa"},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"receiver_shortcode","amount"},
     *     @OA\Property(property="receiver_shortcode", type="string", example="600000"),
     *     @OA\Property(property="amount",             type="number", example=1000),
     *     @OA\Property(property="command_id",         type="string", example="BusinessPayBill"),
     *     @OA\Property(property="account_ref",        type="string", example="INV-B2B-001"),
     *     @OA\Property(property="remarks",            type="string", example="Supplier payment")
     *   )),
     *   @OA\Response(response=200, description="B2B initiated"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    'POST mpesa/b2b' => 'mpesa/b2b',

    /**
     * @OA\Post(
     *   path="/unipay/mpesa/transaction-status",
     *   summary="Transaction Status Query",
     *   description="Query the current status of any M-Pesa transaction by receipt number.",
     *   tags={"M-Pesa"},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"transaction_id"},
     *     @OA\Property(property="transaction_id",  type="string", example="OEI2AK4DKL"),
     *     @OA\Property(property="identifier_type", type="string", example="4"),
     *     @OA\Property(property="remarks",         type="string", example="Status check")
     *   )),
     *   @OA\Response(response=200, description="Status query accepted")
     * )
     */
    'POST mpesa/transaction-status' => 'mpesa/transaction-status',

    /**
     * @OA\Post(
     *   path="/unipay/mpesa/account-balance",
     *   summary="Account Balance",
     *   description="Query M-Pesa account balance for your registered shortcode.",
     *   tags={"M-Pesa"},
     *   @OA\Response(response=200, description="Balance query accepted")
     * )
     */
    'POST mpesa/account-balance' => 'mpesa/account-balance',

    /**
     * @OA\Post(
     *   path="/unipay/mpesa/reversal",
     *   summary="Transaction Reversal",
     *   description="Initiate a reversal for a completed M-Pesa transaction.",
     *   tags={"M-Pesa"},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"transaction_id","amount"},
     *     @OA\Property(property="transaction_id",   type="string", example="OEI2AK4DKL"),
     *     @OA\Property(property="amount",           type="number", example=100),
     *     @OA\Property(property="receiver_id_type", type="string", example="11"),
     *     @OA\Property(property="remarks",          type="string", example="Reversal")
     *   )),
     *   @OA\Response(response=200, description="Reversal initiated"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    'POST mpesa/reversal' => 'mpesa/reversal',

    /**
     * @OA\Get(
     *   path="/unipay/mpesa/transactions",
     *   summary="List Transactions",
     *   description="Paginated list of M-Pesa transactions. Admins see all; users see their own.",
     *   tags={"M-Pesa"},
     *   @OA\Parameter(name="type",   in="query", @OA\Schema(type="string"), example="C2B"),
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string"), example="SUCCESS"),
     *   @OA\Parameter(name="page",   in="query", @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Paginated transactions list")
     * )
     */
    'GET mpesa/transactions' => 'mpesa/transactions',

    /**
     * @OA\Get(
     *   path="/unipay/mpesa/transactions/{id}",
     *   summary="Get Transaction",
     *   description="Retrieve a single transaction with responses and reconciliation data.",
     *   tags={"M-Pesa"},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Transaction detail"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    'GET mpesa/transactions/<id:\d+>' => 'mpesa/transaction',

    /**
     * Callbacks
     */

    'POST mpesa/stk-callback'       => 'mpesa/stk-callback',
    'POST mpesa/c2b-validation'     => 'mpesa/c2b-validation',
    'POST mpesa/c2b-confirmation'   => 'mpesa/c2b-confirmation',
    'POST mpesa/b2c-result'         => 'mpesa/b2c-result',
    'POST mpesa/b2c-timeout'        => 'mpesa/b2c-timeout',
    'POST mpesa/b2b-result'         => 'mpesa/b2b-result',
    'POST mpesa/b2b-timeout'        => 'mpesa/b2b-timeout',
    'POST mpesa/status-result'      => 'mpesa/status-result',
    'POST mpesa/status-timeout'     => 'mpesa/status-timeout',
    'POST mpesa/balance-result'     => 'mpesa/balance-result',
    'POST mpesa/balance-timeout'    => 'mpesa/balance-timeout',
    'POST mpesa/reversal-result'    => 'mpesa/reversal-result',
    'POST mpesa/reversal-timeout'   => 'mpesa/reversal-timeout',
];