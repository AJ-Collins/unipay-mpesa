<?php
return [
    /**
     * @OA\Post(
     * path="/iam/auth/register",
     * summary="Register",
     * description="Register a new user account",
     * security={{}},
     * tags={"Authentication"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Provide Username & Password",
     *    @OA\JsonContent(
     *     required={"first_name","last_name","email_address","mobile_number","username","password","confirm_password"},
     *       ref="#/components/schemas/Register"
     *    ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *               ref="#/components/schemas/Register"
     *          ),
     *          @OA\Property(property="alertify", type="object",
     *              @OA\Property(property="message", type="string", example="Registration successful. Please check your email to verify your account before logging in."),
     *              @OA\Property(property="theme", type="string", example="success"),
     *              @OA\Property(property="type", type="string", example="alert"),
     *          ),
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Data Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *          @OA\Property(property="errors", type="object",
     *               ref="#/components/schemas/Register"
     *          )
     *       )
     *    )
     * )
     *),
     */
    'POST auth/register' => 'auth/register',

    /**
     * @OA\Get(
     *     path="/iam/auth/activate",
     *     tags={"Authentication"},
     *     summary="Activate Account",
     *     description="Activate user account using verification token sent via email",
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Verification token from email"
     *     ),
     *     @OA\Response(
     *      response=200, 
     *      description="Account activated or error message", 
     *      @OA\JsonContent(
     *          @OA\Property(property="alertifyPayload", type="object",
     *             @OA\Property(property="message", type="string", example="Your account has been successfully activated! You can now log in."),
     *             @OA\Property(property="type", type="string", example="toast"),
     *             @OA\Property(property="theme", type="string", example="success"),
     *          )
     *      )
     * ),
     *     @OA\Response(
     *      response=400, 
     *      description="Invalid or expired token", 
     *      @OA\JsonContent(
     *          @OA\Property(property="alertifyPayload", type="object",
     *             @OA\Property(property="message", type="string", example="Invalid or expired activation link."),
     *             @OA\Property(property="type", type="string", example="alert"),
     *             @OA\Property(property="theme", type="string", example="danger"),
     *          )
     *      )
     * ),
     * )
     */
    'GET auth/activate' => 'auth/activate',


    /**
     * @OA\Post(
     * path="/iam/auth/login",
     * summary="Login",
     * description="Authenticate user and generate access token",
     * security={{}},
     * tags={"Authentication"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Provide Username & Password",
     *    @OA\JsonContent(
     *       required={"username","password"},
     *       @OA\Property(property="username", type="string", title="Username", example="admin"),
     *       @OA\Property(property="password", type="string", title="Password", example="@dmiN123"),
     *    ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *              @OA\Property(property="access_token", type="string", title="Token", example="xxx.xxx.xxx"),
     *          ),
     *          @OA\Property(property="alertify", type="object",
     *              @OA\Property(property="message", type="string", example="Access Granted"),
     *              @OA\Property(property="theme", type="string", example="success"),
     *              @OA\Property(property="type", type="string", example="toast"),
     *          ),
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Data Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *          @OA\Property(property="errors", type="object",
     *              @OA\Property(property="username", type="string", title="Username", example="admin"),
     *              @OA\Property(property="password", type="string", title="Password", example="admin"),
     *          )
     *       )
     *    )
     * )
     *),
     */
    'POST auth/login' => 'auth/login',


    /**
     * @OA\Get(
     *     path="/iam/auth/me",
     *     tags={"Authentication"},
     *     summary="Logged In User",
     *    description="Retrieve information about the currently authenticated user",
     * @OA\Response(
     *    response=201,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *               ref="#/components/schemas/Me"
     *          )
     *       )
     *    )
     * ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    'GET auth/me' => 'auth/me',


    /**
     * @OA\Post(
     * path="/iam/auth/refresh",
     * summary="Refresh Token",
     * description="Refresh access token using a valid refresh token",
     * tags={"Authentication"},
     * @OA\Response(
     *    response=401,
     *    description="Unauthorized",
     *    @OA\JsonContent(
     *          @OA\Property(property="errors", type="object",
     *              @OA\Property(property="statusCode", type="integer",  example=401),
     *              @OA\Property(property="message", type="string",  example="Invalid refresh token"),
     *          )
     *       
     *    )
     * )
     *),
     */
    'POST auth/refresh' => 'auth/refresh',

    /**
     * @OA\Put(
     * path="/iam/auth/change-password",
     * summary="Change Password",
     * description="Change password for authenticated user",
     * tags={"Authentication"},
     * @OA\RequestBody(required=true,description="Provide old and new passwords",
     *    @OA\JsonContent(required={"currentPassword","newPassword"},ref="#/components/schemas/ChangePassword"),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *              @OA\Property(property="message", type="string", example="Password changed successfully."),
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object",
     *              @OA\Property(property="currentPassword", type="string",  example="Validation error occured"),
     *          ),
     *       )
     *    )
     * )
     *),
     */
    'PUT auth/change-password' => 'auth/change-password',

    /**
     * @OA\Post(
     * path="/iam/auth/request-password-reset",
     * summary="Request Pass. Reset",
     * description="Request password reset link to be sent via email",
     * tags={"Authentication"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Provide Username",
     *    @OA\JsonContent(
     *       required={"username"},
     *       @OA\Property(property="username", type="string", title="Username", example="admin"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *          @OA\Property(property="type", type="object",
     *              @OA\Property(property="message", type="string", example="Password reset link has been sent to your email."),
     *              @OA\Property(property="theme", type="string", example="success"),
     *              @OA\Property(property="type", type="string", example="alert"),
     *          ),
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Data Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object",
     *              @OA\Property(property="username", type="string", title="Username", example="Wrong username provided"),
     *          ),
     *       )
     *    )
     * )
     *),
     */
    'POST auth/request-password-reset' => 'auth/request-password-reset',

    /**
     * @OA\Put(
     * path="/iam/auth/reset-password",
     * summary="Reset Password",
     * description="Reset user password using a valid reset token",
     * security={{}},
     * tags={"Authentication"},
     * @OA\RequestBody(required=true,description="Provide new password",
     *    @OA\JsonContent(required={"currentPassword","password"},ref="#/components/schemas/ResetPassword"),
     * ),
     * @OA\Parameter(
     *    name="token",
     *    in="query",
     *    required=true,
     *    description="Password reset token",
     *    @OA\Schema(type="string", example="WfITRXSSt2g8pObPda_rg4e3CN_PGmqL")
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *          @OA\Property(property="type", type="object",
     *              @OA\Property(property="route", type="string", title="route", example="iam/auth/login"),
     *              @OA\Property(property="message", type="string", title="message", example="Password has been reset successfully. Login with your new password."),
     *          ),
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Data Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *          @OA\Property(property="errors", type="object",
     *              @OA\Property(property="password", type="string", title="password", example="This field is required"),
     *              @OA\Property(property="confrimPassword", type="string", title="confirmPassword", example="This field is required"),
     *          ),
     *       )
     *    )
     * )
     *),
     */
    'PUT auth/reset-password' => 'auth/reset-password',


    /**
     * @OA\Post(
     * path="/iam/auth/logout",
     * summary="Logout",
     * description="Logout user by revoking refresh token",
     * tags={"Authentication"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Devices info for logout",
     *    @OA\JsonContent(
     *       required={"device"},
     *       @OA\Property(property="device", type="string", title="Device", example="current"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Payload Response",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *          @OA\Property(property="message", type="string", example="Logged out successfully."),
     *          @OA\Property(property="theme", type="string", example="info"),
     *          @OA\Property(property="type", type="string", example="toast"),
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=401,
     *    description="Unauthorized",
     *    @OA\JsonContent(
     *       @OA\Property(property="errors", type="object",
     *          @OA\Property(property="statusCode", type="integer", example=401),
     *          @OA\Property(property="message", type="string", example="Invalid or expired token"),
     *       )
     *    )
     * )
     *),
     */
    'POST auth/logout' => 'auth/logout',
];
