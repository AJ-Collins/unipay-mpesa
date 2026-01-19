<?php
return [
    /**
     * @OA\Get(path="/iam/rbac/rule",
     * summary="System Rules",
     * description="Retrieve a list of all system rules available in the RBAC system.", 
     * tags={"Authorization"},
     *   @OA\Response(
     *     response=200,
     *     description="Data payload",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object",
     *              @OA\Property(property="data", type="array",@OA\Items(
     *                 @OA\Property(property="id", type="string", title="Rule ID", example="is_admin"),
     *                 @OA\Property(property="rule_name", type="string", title="Rule Name", example="isAdmin"),
     *                 @OA\Property(property="description", type="string", title="Description", example="Checks if the user is an administrator."),
     *              )),
     *              @OA\Property(property="countOnPage", type="integer", example="25"),
     *              @OA\Property(property="totalCount", type="integer",example="50"),
     *              @OA\Property(property="perPage", type="integer",example="25"),
     *              @OA\Property(property="totalPages", type="integer",example="2"),
     *              @OA\Property(property="currentPage", type="integer",example="1"),
     *              @OA\Property(property="paginationLinks", type="object",
     *              @OA\Property(property="first", type="string",example="/v2/iam/rbac/rules?page=1&per-page=25"),
     *              @OA\Property(property="previous", type="string",example="/v2/iam/rbac/rules?page=1&per-page=25"),
     *              @OA\Property(property="self", type="string",example="/v2/iam/rbac/rules?page=1&per-page=25"),
     *              @OA\Property(property="next", type="string",example="/v2/iam/rbac/rules?page=1&per-page=25"),
     *              @OA\Property(property="last", type="string",example="/v2/iam/rbac/rules?page=1&per-page=25"),
     *            ),
     *          )
     *      )
     *   ),
     * )
     */
    'GET rbac/rule'         => 'rule/index',

    /**
     * @OA\Get(
     * path="/iam/rbac/rule/{id}",
     * summary="Get Rule Details",
     * description="Retrieve detailed information about a specific rule identified by its ID.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="Rule ID",
     *    required=true,
     *    @OA\Schema(type="string", example="isAdmin")
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *              @OA\Property(property="rule_name", type="string", title="Rule Name", example="isAdmin"),
     *              @OA\Property(property="description", type="string", title="Description", example="Checks if the user is an administrator."),
     *          )
     *      )
     *    )
     * ),
     * @OA\Response(
     *    response=404,
     *    description="Resource not found",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", 
     *             @OA\Property(property="message", type="string", example="The requested rule does not exist."),
     *         )
     *       )
     *    )
     * )
     *)
     */
    'GET rbac/rule/{id}'         => 'rule/manage',

    /**
     * @OA\Put(
     * path="/iam/rbac/rule/{id}",
     * summary="Update Rule Details",
     * description="Updates the details of an existing rule identified by its ID.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="Rule ID",
     *    required=true,
     *    @OA\Schema(type="string", example="isAdmin")
     * ),
     * @OA\RequestBody(
     *    required=true,
     *    description="Fill in rule data",
     *    @OA\JsonContent(
     *       required={"rule_name, description"},
     *       @OA\Property(property="rule_name", type="string", title="Rule Name", example="isAdmin", description="The name of the rule."),
     *      @OA\Property(property="description", type="string", title="Description", example="Checks if the user is an administrator.", description="A brief description of the rule's purpose."),
     *    ),
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *             @OA\Property(property="rule_name", type="string", title="Rule Name", example="isAdmin"),
     *            @OA\Property(property="description", type="string", title="Description", example="Checks if the user is an administrator."),
     *          ),
     *          @OA\Property(property="alertify", type="object",
     *              @OA\Property(property="message", type="string", example="Rule updated successfully"),
     *             @OA\Property(property="theme", type="string", example="success"),
     *             @OA\Property(property="type", type="string", example="toast"),
     *       )
     *      )
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Data Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", 
     *             @OA\Property(property="rule_name", type="string", example="Name cannot be blank."),
     *         )
     *       )
     *    )
     * ),
     *)
     */
    'PUT rbac/rule/{id}'         => 'rule/manage',

    /**
     * @OA\Get(
     * path="/iam/rbac/rules",
     * summary="Get Rule List",
     * description="Retrieve a list of all system rules available for dropdown selection in the RBAC system.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="q",
     *    in="query",
     *    description="Search Query",
     *    required=true,
     *    @OA\Schema(type="string", example="isAdmin")
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *              @OA\Property(property="is_admin", type="string", example="isAdmin"),
     *              @OA\Property(property="is_manager", type="string", example="isManager"),
     *          )
     *      )
     *   )
     * ),
     *)
     */
    'GET rbac/rules'         => 'rule/list',
];
