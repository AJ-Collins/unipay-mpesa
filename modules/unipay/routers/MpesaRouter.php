<?php

return [
    /**
     * @OA\Post(
     * path="/unipay/mpesa/stk-push",
     * summary="Initiate STK Push",
     * description="Trigger an M-Pesa STK Push prompt on the customer's phone",
     * tags={"M-Pesa"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Payment details",
     *    @OA\JsonContent(
     *       required={"amount","phone"},
     *       @OA\Property(property="amount",       type="number", example=500),
     *       @OA\Property(property="phone",        type="string", example="07XXXXXXXX"),
     *       @OA\Property(property="account_ref",  type="string", example="INV-001"),
     *       @OA\Property(property="description",  type="string", example="Payment for order"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="STK Push initiated",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *             @OA\Property(property="CheckoutRequestID",  type="string", example="ws_CO_..."),
     *             @OA\Property(property="MerchantRequestID", type="string", example="..."),
     *             @OA\Property(property="ResponseCode",      type="string", example="0"),
     *             @OA\Property(property="ResponseDescription", type="string", example="Success. Request accepted for processing"),
     *          ),
     *          @OA\Property(property="alertify", type="object",
     *             @OA\Property(property="message", type="string", example="Payment prompt sent to your phone."),
     *             @OA\Property(property="theme",   type="string", example="success"),
     *             @OA\Property(property="type",    type="string", example="toast"),
     *          ),
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *          @OA\Property(property="errors", type="object",
     *             @OA\Property(property="phone",  type="string", example="Invalid phone number format. Expected 254710123456"),
     *             @OA\Property(property="amount", type="string", example="Amount must be greater than 0"),
     *          )
     *       )
     *    )
     * )
     * )
     */
    'POST mpesa/stk-push' => 'mpesa/stk-push',

    /**
     * @OA\Post(
     * path="/unipay/mpesa/stk-query",
     * summary="Query STK Push Status",
     * description="Check the status of a previously initiated STK Push request",
     * tags={"M-Pesa"},
     * @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *       required={"checkout_request_id"},
     *       @OA\Property(property="checkout_request_id", type="string", example="ws_CO_..."),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="STK Push status",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *             @OA\Property(property="ResultCode", type="string", example="0"),
     *             @OA\Property(property="ResultDesc", type="string", example="The service request is processed successfully."),
     *          )
     *       )
     *    )
     * )
     * )
     */
    'POST mpesa/stk-query' => 'mpesa/stk-query',

    /**
     * @OA\Post(
     * path="/unipay/mpesa/b2c/send",
     * summary="Send to Customer",
     * description="Disburse funds from the business shortcode to a customer phone number",
     * tags={"M-Pesa"},
     * @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *       required={"amount","phone"},
     *       @OA\Property(property="amount",     type="number", example=1000),
     *       @OA\Property(property="phone",      type="string", example="07XXXXXXXX"),
     *       @OA\Property(property="remarks",    type="string", example="Salary payment"),
     *       @OA\Property(property="command_id", type="string", example="BusinessPayment",
     *           description="BusinessPayment | SalaryPayment | PromotionPayment"
     *       ),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="B2C request accepted",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *             @OA\Property(property="ConversationID",           type="string", example="AG_20230101_..."),
     *             @OA\Property(property="OriginatorConversationID", type="string", example="..."),
     *             @OA\Property(property="ResponseCode",             type="string", example="0"),
     *             @OA\Property(property="ResponseDescription",      type="string", example="Accept the service request successfully."),
     *          ),
     *          @OA\Property(property="alertify", type="object",
     *             @OA\Property(property="message", type="string", example="Disbursement request submitted successfully."),
     *             @OA\Property(property="theme",   type="string", example="success"),
     *             @OA\Property(property="type",    type="string", example="toast"),
     *          ),
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *          @OA\Property(property="errors", type="object",
     *             @OA\Property(property="phone",  type="string", example="Invalid phone number format. Expected 254710123456"),
     *             @OA\Property(property="amount", type="string", example="Amount must be greater than 0"),
     *          )
     *       )
     *    )
     * )
     * )
     */
    'POST mpesa/b2c/send' => 'mpesa/b2c-send',

    /**
     * @OA\Post(
     * path="/unipay/mpesa/b2b/transfer",
     * summary="Transfer to Business",
     * description="Transfer funds from your shortcode to another business Paybill or Till",
     * tags={"M-Pesa"},
     * @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *       required={"amount","receiver_shortcode"},
     *       @OA\Property(property="amount",             type="number", example=5000),
     *       @OA\Property(property="receiver_shortcode", type="string", example="174379"),
     *       @OA\Property(property="account_ref",        type="string", example="Supplier-001"),
     *       @OA\Property(property="remarks",            type="string", example="Supplier payment"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="B2B request accepted",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *             @OA\Property(property="ConversationID",           type="string", example="AG_20230101_..."),
     *             @OA\Property(property="OriginatorConversationID", type="string", example="..."),
     *             @OA\Property(property="ResponseCode",             type="string", example="0"),
     *             @OA\Property(property="ResponseDescription",      type="string", example="Accept the service request successfully."),
     *          ),
     *          @OA\Property(property="alertify", type="object",
     *             @OA\Property(property="message", type="string", example="Transfer request submitted successfully."),
     *             @OA\Property(property="theme",   type="string", example="success"),
     *             @OA\Property(property="type",    type="string", example="toast"),
     *          ),
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *          @OA\Property(property="errors", type="object",
     *             @OA\Property(property="receiver_shortcode", type="string", example="Receiver shortcode is required"),
     *             @OA\Property(property="amount",             type="string", example="Amount must be greater than 0"),
     *          )
     *       )
     *    )
     * )
     * )
     */
    'POST mpesa/b2b/transfer' => 'mpesa/b2b-transfer',
];