<?php
return [
    /**
     * @OA\Get(path="/iam/rbac/permissions",
     * description="Lists all available permissions",
     * summary="Permissions List",
     *   tags={"Authorization"},
     *   @OA\Response(
     *     response=200,
     *     description="Returns a data payload object for all permissions",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object",
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="permission_id", type="string", title="Permission ID", example="iamPermission"),
     *                  @OA\Property(property="permission_name", type="string", title="Permission Name", example="access to system permissions"),
     *                 @OA\Property(property="description", type="string", title="Description", example="Allows access to permission management"),
     *              ),
     *              @OA\Property(property="countOnPage", type="integer", example="25"),
     *              @OA\Property(property="totalCount", type="integer",example="50"),
     *              @OA\Property(property="perPage", type="integer",example="25"),
     *              @OA\Property(property="totalPages", type="integer",example="2"),
     *              @OA\Property(property="currentPage", type="integer",example="1"),
     *              @OA\Property(property="paginationLinks", type="object",
     *              @OA\Property(property="first", type="string",example="/v2/iam/rbac/permission?page=1&per-page=25"),
     *              @OA\Property(property="previous", type="string",example="/v2/iam/rbac/permission?page=1&per-page=25"),
     *              @OA\Property(property="self", type="string",example="/v2/iam/rbac/permission?page=1&per-page=25"),
     *              @OA\Property(property="next", type="string",example="/v2/iam/rbac/permission?page=1&per-page=25"),
     *              @OA\Property(property="last", type="string",example="/v2/iam/rbac/permission?page=1&per-page=25"),
     *            ),
     *          )
     *      )
     *   ),
     * )
     */
    'GET rbac/permissions'         => 'permission/index',

    /**
     * @OA\Get(
     * path="/iam/rbac/permission/{id}",
     * summary="View Permission ",
     * description="Retrieve detailed information about a specific permission by its ID.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    required=true,
     *    @OA\Schema(type="string", example="iamAccessControl")
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",ref="#/components/schemas/Permissions")
     *      )
     *    )
     * ),
     * @OA\Response(
     *    response=404,
     *    description="Resource not found",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", 
     *             @OA\Property(property="message", type="string", example="The requested permission does not exist."),
     *         )
     *       )
     *    )
     * )
     *),
     */
    'GET rbac/permission/{id}'         => 'permission/view',

    /**
     * @OA\Put(
     * path="/iam/rbac/permission/{id}",
     * summary="Update Permission ",
     * description="Update the details of an existing permission identified by its ID.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    required=true,
     *    @OA\Schema(type="string", example="iamAccessControl")
     * ),
     * @OA\RequestBody(
     *    required=true,
     *    description="Fill in permission data",
     *    @OA\JsonContent(
     *       required={"name"},
     *       ref="#/components/schemas/Permissions",
     *    ),
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",ref="#/components/schemas/Permissions"),
     *          @OA\Property(property="alertify", type="object",
     *              @OA\Property(property="message", type="string", example="Permission updated successfully"),
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
     *             @OA\Property(property="name", type="string", example="Name cannot be blank."),
     *         )
     *       )
     *    )
     * ),
     *)
     */
    'PUT rbac/permission/{id}'         => 'permission/update',

    /**
     * @OA\Get(path="/iam/rbac/roles",
     * summary = "Roles List",
     * description="Lists all available roles",
     *   tags={"Authorization"},
     *   @OA\Response(
     *     response=200,
     *     description="Returns a data payload object for all roles",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object",
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="role_id", type="string", title="Role ID", example="su"),
     *                 @OA\Property(property="role_name", type="string", title="Role Name", example="Super User"),
     *                  @OA\Property(property="description", type="string", title="Description", example="Full access to the system"),
     *              ),
     *              @OA\Property(property="countOnPage", type="integer", example="25"),
     *              @OA\Property(property="totalCount", type="integer",example="50"),
     *              @OA\Property(property="perPage", type="integer",example="25"),
     *              @OA\Property(property="totalPages", type="integer",example="2"),
     *              @OA\Property(property="currentPage", type="integer",example="1"),
     *              @OA\Property(property="paginationLinks", type="object",
     *              @OA\Property(property="first", type="string",example="/v2/iam/rbac/roles?page=1&per-page=25"),
     *              @OA\Property(property="previous", type="string",example="/v2/iam/rbac/roles?page=1&per-page=25"),
     *              @OA\Property(property="self", type="string",example="/v2/iam/rbac/roles?page=1&per-page=25"),
     *              @OA\Property(property="next", type="string",example="/v2/iam/rbac/roles?page=1&per-page=25"),
     *              @OA\Property(property="last", type="string",example="/v2/iam/rbac/roles?page=1&per-page=25"),
     *            ),
     *          )
     *      )
     *   ),
     * )
     */
    'GET rbac/roles'         => 'role/index',

    /**
     * @OA\Post(
     * path="/iam/rbac/role",
     * summary="Create Role ",
     * description="Creates a new role",
     * tags={"Authorization"},
     * @OA\RequestBody(
     *    required=true,
     *   description="Fill in role data",
     *    @OA\JsonContent(
     *       required={"role_id", "role_name","description"},
     *       @OA\Property(property="role_id", type="string", title="Role ID", example="dev"),
     *       @OA\Property(property="role_name", type="string", title="Role Name", example="Developer"),
     *       @OA\Property(property="description", type="string", title="Description", example="Developer Role with limited access"),
     *    ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",ref="#/components/schemas/Roles"),
     *          @OA\Property(property="alertify", type="object",
     *              @OA\Property(property="message", type="string", example="Role created successfully"),
     *             @OA\Property(property="theme", type="string", example="success"),
     *             @OA\Property(property="type", type="string", example="toast"),
     *       )
     *      )
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Data Validation Errors",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", 
     *            @OA\Property(property="role_id", type="string", example="Role ID cannot be blank."),
     *            @OA\Property(property="role_name", type="string", example="Role Name cannot be blank."),
     *        )
     *       )
     *    )
     * )
     *),
     */
    'POST rbac/role'         => 'role/create',


    /**
     * @OA\Get(
     * path="/iam/rbac/role/{id}",
     * summary="View Role ",
     * description="Retrieve detailed information about a specific role by its ID.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    required=true,
     *    @OA\Schema(type="string", example="dev")
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *              @OA\Property(property="role_name", type="string", title="Role Name", example="Developer"),
     *              @OA\Property(property="description", type="string", title="Description", example="Developer Role with limited access"),
     *              @OA\Property(property="items", type="object", 
     *                  @OA\Property(property="available", type="object",
     *                      @OA\Property(property="adminSettingsManage", type="object",
     *                          @OA\Property(property="type", type="string", example="permission"),
     *                          @OA\Property(property="display_name", type="string", example="Manage Admin Settings"),
     *                      ),
     *                      @OA\Property(property="userManage", type="object",
     *                          @OA\Property(property="type", type="string", example="permission"),
     *                          @OA\Property(property="display_name", type="string", example="Manage Users"),
     *                      ),
     *                  ),
     *                 @OA\Property(property="assigned", type="object",
     *                      @OA\Property(property="iamGroupEdit", type="object",
     *                          @OA\Property(property="type", type="string", example="permission"),
     *                          @OA\Property(property="display_name", type="string", example="Edit Groups"),
     *                      ),
     *                      @OA\Property(property="creator", type="object",
     *                          @OA\Property(property="type", type="string", example="role"),
     *                          @OA\Property(property="display_name", type="string", example="Creator"),
     *                      ),
     *                 ),
     *          ),
     * ),
     *      )
     *    )
     * ),
     * @OA\Response(
     *    response=404,
     *    description="Resource not found",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", 
     *             @OA\Property(property="message", type="string", example="The requested role does not exist."),
     *         )
     *       )
     *    )
     * )
     *),
     */
    'GET rbac/role/{id}'         => 'role/view',

    /**
     * @OA\Put(
     * path="/iam/rbac/role/{id}",
     * summary="Update Role ",
     * description="Update the details of an existing role identified by its ID.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    required=true,
     *    @OA\Schema(type="string", example="dev")
     * ),
     * @OA\RequestBody(
     *    required=true,
     *    description="Fill in role data",
     *    @OA\JsonContent(
     *       ref="#/components/schemas/Roles",
     *    ),
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",ref="#/components/schemas/Roles"),
     *          @OA\Property(property="alertify", type="object",
     *              @OA\Property(property="message", type="string", example="Role updated successfully"),
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
     *             @OA\Property(property="role_name", type="string", example="Name cannot be blank."),
     *         )
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=404,
     *    description="Resource not found",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", 
     *             @OA\Property(property="message", type="string", example="The requested role does not exist."),
     *         )
     *       )
     *    )
     * )
     *),
     */
    'PUT rbac/role/{id}'         => 'role/update',

    /**
     * @OA\Delete(
     * path="/iam/rbac/role/{id}",
     * summary="Delete Role ",
     * description="Deletes an existing role identified by its ID.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    description="Role ID",
     *    in="path",
     *    required=true,
     *    @OA\Schema(type="string", example="dev")
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Opereation Successful",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *          @OA\Property(property="message", type="string", example="Role deleted successfully"),
     *          @OA\Property(property="theme", type="string", example="success"),
     *          @OA\Property(property="type", type="string", example="toast"),
     *         )
     *       )
     *    )
     * )
     *),
     */
    'DELETE rbac/role/{id}'         => 'role/delete',

    /**
     * @OA\Post(
     * path="/iam/rbac/role/assign/{id}",
     * summary="Assign Permissions",
     * description="Assigns permissions to a role.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    description="Role ID",
     *    in="path",
     *    required=true,
     *    @OA\Schema(type="string", example="dev")
     * ),
     * @OA\RequestBody(
     *     required=true,
     *     description="Provide permissions to assign",
     *     @OA\JsonContent(
     *         type="object",
     *         required={"permissions"},
     *         @OA\Property(property="permissions", type="array", description="List of permission names to assign",
     *             @OA\Items(type="string"),
     *             example={"creator", "iamAccessControl", "viewer", "iamRoleAdd"}
     *         )
     *     )
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *              @OA\Property(property="available", type="object",
     *                  @OA\Property(property="creator", type="string", example="role"),
     *                  @OA\Property(property="iamRoleAssign", type="string", example="permission"),
     *              ),
     *              @OA\Property(property="assigned", type="object",
     *                  @OA\Property(property="iamRoleAdd", type="string", example="permission"),
     *                  @OA\Property(property="viewer", type="string", example="role"),
     *              ),
     *          ),
     *          @OA\Property(property="alertify", type="object",
     *              @OA\Property(property="message", type="string", example="2 permissions assigned successfully"),
     *             @OA\Property(property="theme", type="string", example="success"),
     *             @OA\Property(property="type", type="string", example="toast"),
     *       )
     *      )
     *    )
     * )
     *),
     */
    'POST rbac/role/assign/{id}'         => 'role/assign',

    /**
     * @OA\Post(
     * path="/iam/rbac/role/remove/{id}",
     * summary="Remove Permissions",
     * description="Removes permissions from a role.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *   description="Role ID",
     *    required=true,
     *    @OA\Schema(type="string", example="dev")
     * ),
     * @OA\RequestBody(
     *     required=true,
     *     description="Provide permissions to assign",
     *     @OA\JsonContent(
     *         type="object",
     *         required={"permissions"},
     *         @OA\Property(property="permissions", type="array", description="List of permission names to assign",
     *             @OA\Items(type="string"),
     *             example={"creator", "iamAccessControl", "viewer", "iamRoleAdd"}
     *         )
     *     )
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *              @OA\Property(property="available", type="object",
     *                  @OA\Property(property="creator", type="string", example="role"),
     *                  @OA\Property(property="iamRoleAssign", type="string", example="permission"),
     *              ),
     *              @OA\Property(property="assigned", type="object",
     *                  @OA\Property(property="iamRoleAdd", type="string", example="permission"),
     *                  @OA\Property(property="viewer", type="string", example="role"),
     *              ),
     *          ),
     *          @OA\Property(property="alertify", type="object",
     *              @OA\Property(property="message", type="string", example="3 permissions removed successfully"),
     *             @OA\Property(property="theme", type="string", example="success"),
     *             @OA\Property(property="type", type="string", example="toast"),
     *       )
     *      )
     *    )
     * )
     *),
     */
    'POST rbac/role/remove/{id}'         => 'role/remove',

    /**
     * @OA\Get(path="/iam/rbac/groups",
     * summary="Group Lists",
     * description="Lists all available roles",  
     * tags={"Authorization"},
     *   @OA\Response(
     *     response=200,
     *     description="Returns a data payload object for all groups",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object",
     *              @OA\Property(property="data", type="array",@OA\Items(ref="#/components/schemas/Groups")),
     *              @OA\Property(property="countOnPage", type="integer", example="25"),
     *              @OA\Property(property="totalCount", type="integer",example="50"),
     *              @OA\Property(property="perPage", type="integer",example="25"),
     *              @OA\Property(property="totalPages", type="integer",example="2"),
     *              @OA\Property(property="currentPage", type="integer",example="1"),
     *              @OA\Property(property="paginationLinks", type="object",
     *              @OA\Property(property="first", type="string",example="/v2/iam/rbac/groups?page=1&per-page=25"),
     *              @OA\Property(property="previous", type="string",example="/v2/iam/rbac/groups?page=1&per-page=25"),
     *              @OA\Property(property="self", type="string",example="/v2/iam/rbac/groups?page=1&per-page=25"),
     *              @OA\Property(property="next", type="string",example="/v2/iam/rbac/groups?page=1&per-page=25"),
     *              @OA\Property(property="last", type="string",example="/v2/iam/rbac/groups?page=1&per-page=25"),
     *            ),
     *          )
     *      )
     *   ),
     * )
     */
    'GET rbac/groups'         => 'group/index',

    /**
     * @OA\Post(
     * path="/iam/rbac/group",
     * summary="Create Group ",
     * description="Creates a new group",
     * tags={"Authorization"},
     * @OA\RequestBody(
     *    required=true,
     *   description="Fill in group data",
     *    @OA\JsonContent(
     *       required={"group_id", "group_name","description"},
     *       @OA\Property(property="group_id", type="string", title="Group ID", example="dev-team"),
     *       @OA\Property(property="group_name", type="string", title="Group Name", example="Developer Group"),
     *       @OA\Property(property="description", type="string", title="Description", example="Group for all developers"),
     *    ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",ref="#/components/schemas/Groups"),
     *          @OA\Property(property="alertify", type="object",
     *              @OA\Property(property="message", type="string", example="Group created successfully"),
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
     *             @OA\Property(property="group_name", type="string", example="Group name already exists"),
     *        )
     *       )
     *    )
     * )
     *)
     */
    'POST rbac/group'         => 'group/create',

    /**
     * @OA\Get(
     * path="/iam/rbac/group/{id}",
     * summary="View Group ",
     * description="Retrieve detailed information about a specific group by its ID.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="Group ID",
     *    required=true,
     *    @OA\Schema(type="string", example="dev-team")
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *              @OA\Property(property="group_name", type="string", title="Group Name", example="Developer Group"),
     *              @OA\Property(property="description", type="string", title="Description", example="Developer Group with limited access"),
     *              @OA\Property(property="items", type="object", 
     *                  @OA\Property(property="available", type="object",
     *                      @OA\Property(property="su", type="object",
     *                          @OA\Property(property="type", type="string", example="role"),
     *                          @OA\Property(property="display_name", type="string", example="Super User"),
     *                      ),
     *                      @OA\Property(property="user", type="object",
     *                          @OA\Property(property="type", type="string", example="role"),
     *                          @OA\Property(property="display_name", type="string", example="User"),
     *                      ),
     *                  ),
     *                 @OA\Property(property="assigned", type="object",
     *                      @OA\Property(property="viewer", type="object",
     *                          @OA\Property(property="type", type="string", example="role"),
     *                          @OA\Property(property="display_name", type="string", example="Viewer"),
     *                      ),
     *                      @OA\Property(property="creator", type="object",
     *                          @OA\Property(property="type", type="string", example="role"),
     *                          @OA\Property(property="display_name", type="string", example="Creator"),
     *                      ),
     *                 ),
     *          ),
     * ),
     *      )
     *    )
     * ),
     * @OA\Response(
     *    response=404,
     *    description="Resource not found",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", 
     *             @OA\Property(property="message", type="string", example="The requested group does not exist."),
     *         )
     *       )
     *    )
     * )
     *)
     */
    'GET rbac/group/{id}'         => 'group/view',

    /**
     * @OA\Put(
     * path="/iam/rbac/group/{id}",
     * summary="Update Group ",
     * description="Update the details of an existing group identified by its ID.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="Group ID",
     *    required=true,
     *    @OA\Schema(type="string", example="dev-team")
     * ),
     * @OA\RequestBody(
     *    required=true,
     *    description="Fill in group data",
     *    @OA\JsonContent(
     *       required={"group_name"},
     *       ref="#/components/schemas/Groups",
     *    ),
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",ref="#/components/schemas/Groups"),
     *          @OA\Property(property="alertify", type="object",
     *              @OA\Property(property="message", type="string", example="Group updated successfully"),
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
     *             @OA\Property(property="group_name", type="string", example="Name cannot be blank."),
     *         )
     *       )
     *    )
     * ),
     *)
     */
    'PUT rbac/group/{id}'         => 'group/update',

    /**
     * @OA\Delete(
     * path="/iam/rbac/group/{id}",
     * summary="Delete Group ",
     * description="Deletes an existing group identified by its ID.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    description="Group ID",
     *    in="path",
     *    required=true,
     *    @OA\Schema(type="string", example="dev-team")
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Operation Successful",
     *    @OA\JsonContent(
     *       @OA\Property(property="alertifyPayload", type="object",
     *          @OA\Property(property="message", type="string", example="Group deleted successfully"),
     *          @OA\Property(property="theme", type="string", example="success"),
     *          @OA\Property(property="type", type="string", example="toast"),
     *         )
     *       )
     * ),
     * @OA\Response(
     *    response=404,
     *    description="Resource not found",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", 
     *             @OA\Property(property="message", type="string", example="The requested group does not exist."),
     *         )
     *       )
     *    )
     * )
     *)
     */
    'DELETE rbac/group/{id}'         => 'group/delete',

    /**
     * @OA\Post(
     * path="/iam/rbac/group/assign/{id}",
     * summary="Assign Roles",
     * description="Assigns roles to a group.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    description="Group ID",
     *    in="path",
     *    required=true,
     *    @OA\Schema(type="string", example="dev-team")
     * ),
     * @OA\RequestBody(
     *     required=true,
     *     description="Provide roles to assign",
     *     @OA\JsonContent(
     *         type="object",
     *         required={"roles"},
     *         @OA\Property(property="roles", type="array", description="List of role names to assign",
     *             @OA\Items(type="string"),
     *             example={"creator", "deletor", "viewer", "editor"}
     *         )
     *     )
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *              @OA\Property(property="available", type="object",
     *                  @OA\Property(property="creator", type="string", example="role"),
     *                  @OA\Property(property="editor", type="string", example="role"),
     *              ),
     *              @OA\Property(property="assigned", type="object",
     *                  @OA\Property(property="deletor", type="string", example="role"),
     *                  @OA\Property(property="viewer", type="string", example="role"),
     *              ),
     *          ),
     *          @OA\Property(property="alertify", type="object",
     *              @OA\Property(property="message", type="string", example="2 roles assigned successfully"),
     *             @OA\Property(property="theme", type="string", example="success"),
     *             @OA\Property(property="type", type="string", example="toast"),
     *       )
     *      )
     *    )
     * )
     *),
     */
    'POST rbac/group/assign/{id}'         => 'group/assign',

    /**
     * @OA\Post(
     * path="/iam/rbac/group/remove/{id}",
     * summary="Remove Roles",
     * description="Removes roles from a group.",
     * tags={"Authorization"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *   description="Group ID",
     *    required=true,
     *    @OA\Schema(type="string", example="dev-team")
     * ),
     * @OA\RequestBody(
     *     required=true,
     *     description="Provide roles to assign",
     *     @OA\JsonContent(
     *         type="object",
     *         required={"roles"},
     *         @OA\Property(property="roles", type="array", description="List of role names to assign",
     *             @OA\Items(type="string"),
     *             example={"creator", "deletor", "viewer", "editor"}
     *         )
     *     )
     * ),
     * @OA\Response(
     *    response=202,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",
     *              @OA\Property(property="available", type="object",
     *                  @OA\Property(property="creator", type="string", example="role"),
     *                  @OA\Property(property="editor", type="string", example="role"),
     *              ),
     *              @OA\Property(property="assigned", type="object",
     *                  @OA\Property(property="deletor", type="string", example="role"),
     *                  @OA\Property(property="viewer", type="string", example="role"),
     *              ),
     *          ),
     *          @OA\Property(property="alertify", type="object",
     *              @OA\Property(property="message", type="string", example="3 roles removed successfully"),
     *             @OA\Property(property="theme", type="string", example="success"),
     *             @OA\Property(property="type", type="string", example="toast"),
     *       )
     *      )
     *    )
     * )
     *),
     */
    'POST rbac/group/remove/{id}'         => 'group/remove',

];
