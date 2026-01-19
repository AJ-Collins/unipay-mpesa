<?php
return [
    /**
     * @OA\Get(path="/iam/users",
     * summary="Get all users",
     * description="Retrieve a list of all users in the system with their profiles and statuses.",
     *   tags={"User Management"},
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     required=false,
     *     description="Page number for pagination",
     *     @OA\Schema(type="integer", default=1)
     *   ),
     * @OA\Parameter(
     *     name="per-page",
     *     in="query",
     *     required=false,
     *     description="Number of items per page",
     *     @OA\Schema(type="integer", default=25)
     *   ),
     * @OA\Parameter(
     *     name="q",
     *     in="query",
     *     required=false,
     *     description="Global search query to filter users",
     *     @OA\Schema(type="string", example="admin")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Returns a data payload object for all roles",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object",
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="username", type="string", title="Username", example="admin"),
     *                  @OA\Property(property="profile", type="object",
     *                      @OA\Property(property="first_name", type="string", title="First name", example="Super"),
     *                      @OA\Property(property="middle_name", type="string", title="Middle name", example="Admin"),
     *                      @OA\Property(property="last_name", type="string", title="Last name", example="User"),
     *                      @OA\Property(property="email_address", type="string", title="Email address", example="admin@example.com"),
     *                      @OA\Property(property="phone_number", type="string", title="Phone number", example="0700000000"),
     *                      @OA\Property(property="profile_picture", type="string", title="Profile picture", example="https://example.com/img/faces/admin.jpg"),
     *                  ),
     *                  @OA\Property(property="status", type="string", title="Status", example="active"),   
     *              ),
     *              @OA\Property(property="countOnPage", type="integer", example="25"),
     *              @OA\Property(property="totalCount", type="integer",example="50"),
     *              @OA\Property(property="perPage", type="integer",example="25"),
     *              @OA\Property(property="totalPages", type="integer",example="2"),
     *              @OA\Property(property="currentPage", type="integer",example="1"),
     *              @OA\Property(property="paginationLinks", type="object",
     *              @OA\Property(property="first", type="string",example="v1/iam/rbac/users?page=1&per-page=25"),
     *              @OA\Property(property="previous", type="string",example="v1/iam/rbac/users?page=1&per-page=25"),
     *              @OA\Property(property="self", type="string",example="v1/iam/rbac/users?page=1&per-page=25"),
     *              @OA\Property(property="next", type="string",example="v1/iam/rbac/users?page=1&per-page=25"),
     *              @OA\Property(property="last", type="string",example="v1/iam/rbac/users?page=1&per-page=25"),
     *            ),
     *          )
     *      )
     *   ),
     * )
     */
    'GET users'         => 'user/index',

    /**
     * @OA\Get(
     * path="/iam/user/{id}",
     * summary="Get User Details",
     * description="Retrieve detailed information about a specific user by their username.",
     * tags={"User Management"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="The username of the user to view",
     *    required=true,
     *    @OA\Schema(type="string", example="admin"),
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="username", type="string", title="Username", example="admin"),
     *                  @OA\Property(property="profile", type="object",
     *                      @OA\Property(property="first_name", type="string", title="First name", example="Super"),
     *                      @OA\Property(property="middle_name", type="string", title="Middle name", example="Admin"),
     *                      @OA\Property(property="last_name", type="string", title="Last name", example="User"),
     *                      @OA\Property(property="email_address", type="string", title="Email address", example="admin@example.com"),
     *                      @OA\Property(property="phone_number", type="string", title="Phone number", example="0700000000"),
     *                      @OA\Property(property="profile_picture", type="string", title="Profile picture", example="https://example.com/img/faces/admin.jpg"),
     *                  ),
     *                  @OA\Property(property="status", type="string", title="Status", example="active"), 
     *      )
     *    )
     * ),
     * @OA\Response(
     *    response=404,
     *    description="Resource not found",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", 
     *             @OA\Property(property="message", type="string", example="The requested user does not exist."),
     *         )
     *       )
     *    )
     * )
     *),
     */
    'GET user/{id}'         => 'user/view',

    /**
     * @OA\Post(
     * path="/iam/user/create",
     * summary="Create a New User",
     * description="Register a new user in the system.",
     * tags={"User Management"},
     * @OA\RequestBody(
     *    required=true,
     *   description="User registration details",
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
    'POST user/create' => 'user/create',

    /**
     * @OA\Delete(
     * path="/iam/user/{id}",
     * summary="Delete a User",
     * description="Delete a user from the system by their username.",
     * tags={"User Management"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="The username of the user to delete",
     *    required=true,
     *    @OA\Schema(type="string", example="johndoe"),
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Alertify payload",
     *    @OA\JsonContent(
     *     @OA\Property(property="alertifyPayload", type="object",
     *        @OA\Property(property="message", type="string", example="User deleted successfully"),
     *        @OA\Property(property="theme", type="string", example="success"),
     *        @OA\Property(property="type", type="string", example="alert"),
     *     )
     * )
     * ),
     * @OA\Response(
     *    response=404,
     *    description="Resource not found",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", 
     *             @OA\Property(property="message", type="string", example="The requested user does not exist."),
     *         )
     *       )
     *    )
     * )
     *),
     */
    'DELETE user/{id}'         => 'user/toggle-delete',

    /**
     * @OA\Patch(
     * path="/iam/user/{id}",
     * summary="Restore User",
     * description="Restore a previously deleted user in the system by their username.",
     * tags={"User Management"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="The username of the user to restore",
     *    required=true,
     *    @OA\Schema(type="string", example="johndoe"),
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Alertify payload",
     *    @OA\JsonContent(
     *     @OA\Property(property="alertifyPayload", type="object",
     *        @OA\Property(property="message", type="string", example="User restored successfully"),
     *        @OA\Property(property="theme", type="string", example="success"),
     *        @OA\Property(property="type", type="string", example="alert"),
     *     )
     * )
     * ),
     * @OA\Response(
     *    response=404,
     *    description="Resource not found",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", 
     *             @OA\Property(property="message", type="string", example="The requested user does not exist."),
     *         )
     *       )
     *    )
     * )
     *),
     */
    'PATCH user/{id}'         => 'user/toggle-delete',


    /**
     * @OA\Post(
     * path="/iam/user/assign/{username}/{id}",
     * summary="Assign to Group",
     * description="Assign a user to a specific group by their username and group ID.",
     * tags={"User Management"},
     * @OA\Parameter(
     *     name="username",
     *     in="path",
     *     required=true,
     *    description="The username of the user to assign",
     *    @OA\Schema(type="string", example="johndoe")
     *   ),
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *    description="The ID of the group to assign the user to",
     *    @OA\Schema(type="string", example="dev-team")
     *   ),
     * @OA\Response(
     *    response=202,
     *    description="",
     *    @OA\JsonContent(
     *          @OA\Property(property="alertifyPayload", type="object",
     *              @OA\Property(property="message", type="string", example="User added to Dev Team successfully."),
     *             @OA\Property(property="theme", type="string", example="success"),
     *             @OA\Property(property="type", type="string", example="toast"),
     *       )
     *      
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *              @OA\Property(property="message", type="string", example="User already belongs to Dev Team."),
     *              @OA\Property(property="theme", type="string", example="info"),
     *              @OA\Property(property="type", type="string", example="toast"),
     *          )
     *    )
     * )
     *),
     */
    'POST user/assign/{username}/{id}'         => 'user/assign',

    /**
     * @OA\Post(
     * path="/iam/user/remove/{username}/{id}",
     * summary="Remove from Group",
     * description="Remove a user from a specific group by their username and group ID.",
     * tags={"User Management"},
     * @OA\Parameter(
     *     name="username",
     *     in="path",
     *     required=true,
     *   description="The username of the user to remove",
     *    @OA\Schema(type="string", example="johndoe")
     *   ),
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *   description="The ID of the group to remove the user from",
     *    @OA\Schema(type="string", example="dev-team")
     *   ),
     * @OA\Response(
     *    response=202,
     *    description="",
     *    @OA\JsonContent(
     *          @OA\Property(property="alertifyPayload", type="object",
     *              @OA\Property(property="message", type="string", example="User removed from Dev Team successfully."),
     *             @OA\Property(property="theme", type="string", example="success"),
     *             @OA\Property(property="type", type="string", example="toast"),
     *       )
     *      
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *              @OA\Property(property="message", type="string", example="User does not belong to Dev Team."),
     *              @OA\Property(property="theme", type="string", example="warning"),
     *              @OA\Property(property="type", type="string", example="toast"),
     *          )
     *    )
     * )
     *),
     */
    'POST user/remove/{username}/{id}'         => 'user/remove',

    /**
     * @OA\Patch(
     * path="/iam/user/status/{username}",
     * summary="Toggle User Status",
     * description="Activate or deactivate a user by their username.",
     * tags={"User Management"},
     * @OA\Parameter(
     *    name="username",
     *    in="path",
     *    description="The username of the user to toggle status",
     *    required=true,
     *    @OA\Schema(type="string", example="johndoe"),
     * ),
     * @OA\Response(
     *    response=202,
     *    description="",
     *    @OA\JsonContent(
     *     @OA\Property(property="alertifyPayload", type="object",
     *        @OA\Property(property="message", type="string", example="User activated/deactivated successfully"),
     *        @OA\Property(property="theme", type="string", example="success"),
     *        @OA\Property(property="type", type="string", example="alert"),
     *     )
     * )
     * )
     *),
     */
    'PATCH user/status/{username}'         => 'user/toggle-status',

    /**
     * @OA\Post(
     * path="/iam/user/ban/{username}",
     * summary="Ban User",
     * description="Ban  a user by their username.",
     * tags={"User Management"},
     * @OA\Parameter(
     *    name="username",
     *    in="path",
     *   description="The username of the user to ban",
     *    required=true,
     *    @OA\Schema(type="string", example="johndoe"),
     * ),
     * @OA\Response(
     *    response=202,
     *    description="",
     *    @OA\JsonContent(
     *     @OA\Property(property="alertifyPayload", type="object",
     *        @OA\Property(property="message", type="string", example="User banned successfully."),
     *        @OA\Property(property="theme", type="string", example="success"),
     *        @OA\Property(property="type", type="string", example="alert"),
     *     )
     * )
     * )
     *),
     */
    'POST user/ban/{username}'         => 'user/ban',

];
