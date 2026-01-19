<?php
return [
     /**
     * @OA\Get(
     * path="/admin/settings/general",
     * summary="General Settings",
     * description="Retrieve general configuration settings",
     * tags={"System Settings"},
     * @OA\Response(
     *    response=200,
     *    description="Data payload for general settings",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object", ref="#/components/schemas/General Settings"),
     *       )
     *    )
     * )
     * ),
     */
    'GET settings/general'         => 'settings/manage-general',
    /**
     * @OA\Post(
     * path="/admin/settings/general",
     * summary="Save General Settings",
     * description="Update general configuration settings",
     * tags={"System Settings"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Fill in general settings  data",
     *    @OA\JsonContent(
     *       ref="#/components/schemas/General Settings",
     *    ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",ref="#/components/schemas/General Settings"),
     * 
     *      @OA\Property(property="alertify", type="object",
     *          @OA\Property(property="message", type="string", example="General settings updated successfully."),
     *          @OA\Property(property="theme", type="string",example="success"),
     *          @OA\Property(property="type", type="string",example="toast"),
     *       )
     *       ),
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Data Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", ref="#/components/schemas/General Settings"),
     *       )
     *    )
     * )
     *),
     */
    'POST settings/general'         => 'settings/manage-general',


    /**
     * @OA\Get(
     * path="/admin/settings/mailer",
     * summary="Mailer Settings",
     * description="Retrieve mailer configuration settings",
     * tags={"System Settings"},
     * @OA\Response(
     *    response=200,
     *    description="Data payload for mailer settings",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object", ref="#/components/schemas/Mailer Settings"),
     *       )
     *    )
     * )
     * ),
     */
    'GET settings/mailer'         => 'settings/manage-mailer',
    /**
     * @OA\Post(
     * path="/admin/settings/mailer",
     * summary="Save Mailer Settings",
     * description="Update mailer configuration settings",
     * tags={"System Settings"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Fill in email settings  data",
     *    @OA\JsonContent(
     *       ref="#/components/schemas/Mailer Settings",
     *    ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",ref="#/components/schemas/Mailer Settings"),
     * 
     *      @OA\Property(property="alertify", type="object",
     *          @OA\Property(property="message", type="string", example="Mailer settings updated successfully."),
     *          @OA\Property(property="theme", type="string",example="success"),
     *          @OA\Property(property="type", type="string",example="toast"),
     *       )
     *       ),
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Data Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", ref="#/components/schemas/Mailer Settings"),
     *       )
     *    )
     * )
     *),
     */
    'POST settings/mailer'         => 'settings/manage-mailer',


    /**
     * @OA\Get(
     * path="/admin/settings/theme",
     * summary="Theme Settings",
     * description="Retrieve theme configuration settings",
     * tags={"System Settings"},
     * @OA\Response(
     *    response=200,
     *    description="Data payload for theme settings",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object", ref="#/components/schemas/Theme & Appearance"),
     *       )
     *    )
     * )
     * ),
     */
    'GET settings/theme'         => 'settings/manage-theme',
    /**
     * @OA\Post(
     * path="/admin/settings/theme",
     * summary="Save Theme Settings",
     * description="Update theme configuration settings",
     * tags={"System Settings"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Fill in  settings  data",
     *    @OA\JsonContent(
     *       ref="#/components/schemas/Theme & Appearance",
     *    ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",ref="#/components/schemas/Theme & Appearance"),
     * 
     *      @OA\Property(property="alertify", type="object",
     *          @OA\Property(property="message", type="string", example="Theme settings updated successfully."),
     *          @OA\Property(property="theme", type="string",example="success"),
     *          @OA\Property(property="type", type="string",example="toast"),
     *       )
     *       ),
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Data Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", ref="#/components/schemas/Theme & Appearance"),
     *       )
     *    )
     * )
     *),
     */
    'POST settings/theme'         => 'settings/manage-theme',


    /**
     * @OA\Get(
     * path="/admin/settings/security",
     * summary="Security Settings",
     * description="Retrieve Privacy & Security configuration settings",
     * tags={"System Settings"},
     * @OA\Response(
     *    response=200,
     *    description="Data payload for security settings",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object", ref="#/components/schemas/Privacy & Security"),
     *       )
     *    )
     * )
     * ),
     */
    'GET settings/security'         => 'settings/manage-security',
    /**
     * @OA\Post(
     * path="/admin/settings/security",
     * summary="Save Security Settings",
     * description="Update Privacy & Security configuration settings",
     * tags={"System Settings"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Fill in  settings  data",
     *    @OA\JsonContent(
     *       ref="#/components/schemas/Privacy & Security",
     *    ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",ref="#/components/schemas/Privacy & Security"),
     * 
     *      @OA\Property(property="alertify", type="object",
     *          @OA\Property(property="message", type="string", example="Security settings updated successfully."),
     *          @OA\Property(property="security", type="string",example="success"),
     *          @OA\Property(property="type", type="string",example="toast"),
     *       )
     *       ),
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Data Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", ref="#/components/schemas/Privacy & Security"),
     *       )
     *    )
     * )
     *),
     */
    'POST settings/security'         => 'settings/manage-security',
];
