<?php
return [
    /**
     * @OA\Get(path="/admin/task/manager",
     *   summary="Get all Tasks  ",
     *   description="Returns a list of scheduled tasks",
     *   tags={"Task Manager"},
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
     *     description="Global search query to filter tasks",
     *     @OA\Schema(type="string", example="mailer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Scheduled tasks.",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object",
     *              @OA\Property(property="data", type="array",@OA\Items(
     *                 type="object",
     *                  @OA\Property(property="task_id", type="integer", title="Task ID", description="Unique identifier for the scheduled task", example="1",),
     *                  @OA\Property(property="task_title", type="string", title="Task Title", description="Title or name of the scheduled task", example="Database Backup",),
     *                  @OA\Property(property="service_name", type="string", title="Service Name", description="Name of the system service", example="Mailer",),
     *                  @OA\Property(property="status", type="object", title="Status", 
     *                         @OA\Property(property="label", type="string",  example="Running",),
     *                          @OA\Property(property="theme", type="string", example="success")
     *                  ),
     *              )
     *         ),
     *          @OA\Property(property="countOnPage", type="integer", example="25"),
     *          @OA\Property(property="totalCount", type="integer",example="50"),
     *          @OA\Property(property="perPage", type="integer",example="25"),
     *          @OA\Property(property="totalPages", type="integer",example="2"),
     *          @OA\Property(property="currentPage", type="integer",example="1"),
     *          @OA\Property(property="paginationLinks", type="object",
     *              @OA\Property(property="first", type="string",example="/v2/admin/task-managers?page=1&per-page=25"),
     *              @OA\Property(property="previous", type="string",example="/v2/admin/task-managers?page=1&per-page=25"),
     *              @OA\Property(property="self", type="string",example="/v2/admin/task-managers?page=1&per-page=25"),
     *              @OA\Property(property="next", type="string",example="/v2/admin/task-managers?page=1&per-page=25"),
     *              @OA\Property(property="last", type="string",example="/v2/admin/task-managers?page=1&per-page=25"),
     *              ),
     *          )
     *      )
     *   ),
     * )
     */
    'GET task/manager'         => 'task-manager/index',

    /**
     * @OA\Get(path="/admin/task/services",
     *  summary="System Services ",
     *  description="Returns a list of available system services for scheduling tasks",
     *  tags={"Task Manager"},
     *  @OA\Response(
     *     response=200,
     *     description="Data payload",
     *     @OA\JsonContent(
     *      @OA\Property(property="dataPayload", type="object",
     *         @OA\Property(
     *             property="data",
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="key", type="string", example="3ykpGgaacd79JxWENFYtg6l+2sJQ8F2hBKB2Dg+xvJg="),
     *                 @OA\Property(property="label", type="string", example="Mailer Service")
     *             )
     *         )
     *      )
     *     )
     *  )
     * )
     */
    'GET task/services'     => 'task-manager/services',

    /**
     * @OA\Get(path="/admin/task/services/{id}",
     *  summary="Service Fields",
     *  description="Returns a list of available system services for scheduling tasks",
     *  tags={"Task Manager"},
     *  @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="Service unique ID to load",
     *    required=true,
     *    @OA\Schema(type="string", example="xxxx"),
     *  ),
     *  @OA\Response(
     *     response=200,
     *     description="Data payload",
     *     @OA\JsonContent(
     *      @OA\Property(property="dataPayload", type="object",
     *         @OA\Property(
     *             property="data",
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="key", type="string", example="3ykpGgaacd79JxWENFYtg6l+2sJQ8F2hBKB2Dg+xvJg="),
     *                 @OA\Property(property="label", type="string", example="Mailer Service")
     *             )
     *         )
     *      )
     *     )
     *  )
     * )
     */
    'GET task/services/{id}'     => 'task-manager/service-fields',

    /**
     * @OA\Post(
     * path="/admin/task/manager",
     * summary="Schedule New Task",
     * description="Creates a new scheduled task",
     * tags={"Task Manager"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Fill in scheduled task data",
     *    @OA\JsonContent(
     *       required={"task_id","task_title","job_class","next_run_at",},
     *       ref="#/components/schemas/Scheduler",
     *    ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Data payload",
     *    @OA\JsonContent(
     *       @OA\Property(property="dataPayload", type="object",
     *          @OA\Property(property="data", type="object",ref="#/components/schemas/Scheduler"),
     *       ),
     *       @OA\Property(property="alertifyPayload", type="object",
     *          @OA\Property(property="message", type="string", example="task-manager created succefully"),
     *          @OA\Property(property="theme", type="string",example="success"),
     *          @OA\Property(property="type", type="string",example="alert"),
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Data Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", ref="#/components/schemas/Scheduler"),
     *       )
     *    )
     * )
     *),
     */
    'POST task/manager'         => 'task-manager/create',

    /**
     * @OA\Get(path="/admin/task/manager/{id}",
     *   summary="Get Task  ",
     *  description="Returns a single scheduled task by ID",
     *   tags={"Task Manager"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="Task unique ID to load",
     *    required=true,
     *    @OA\Schema(type="string", example="xxxx"),
     * ),
     *   @OA\Response(
     *     response=200,
     *     description="Returns a scheduled task.",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object", ref="#/components/schemas/Scheduler"))
     *      ),
     *   @OA\Response(
     *     response=404,
     *     description="Record not found",
     *      @OA\JsonContent(
     *           @OA\Property(property="errorPayload", type="object",
     *               @OA\Property(property="statusCode", type="integer", example=404 ),
     *               @OA\Property(property="message", type="string", example="The requested schedulermodel does not exist" )
     *           )
     *      )
     *   ),
     * )
     */
    'GET task/manager/{id}'     => 'task-manager/view',

    /**
     * @OA\Delete(path="/admin/task/manager/{id}",
     *    tags={"Task Manager"},
     *    summary="Delete Task",
     *   description="Deletes an existing scheduled task.",
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="Task unique ID to delete",
     *    required=true,
     *    @OA\Schema(type="string", example="xxxx"),
     * ),
     *     @OA\Response(
     *         response=202,
     *         description="Deletion successful",
     *         @OA\JsonContent(
     *         @OA\Property(property="alertifyPayload", type="object",
     *            @OA\Property(property="message", type="string", example="Task deleted successfully"),
     *            @OA\Property(property="theme", type="string",example="success"),
     *            @OA\Property(property="type", type="string",example="toast"),
     *         )
     *         )
     *     ),
     *   @OA\Response(
     *     response=404,
     *     description="Record not found",
     *      @OA\JsonContent(
     *           @OA\Property(property="errorPayload", type="object",
     *               @OA\Property(property="statusCode", type="integer", example=404 ),
     *               @OA\Property(property="message", type="string", example="The requested schedulermodel does not exist" )
     *           )
     *      )
     *   )
     * )
     */
    'DELETE task/manager/{id}'  => 'task-manager/toggle-delete',

    /**
     * @OA\Patch(path="/admin/task-manager/{id}",
     *    tags={"Task Manager"},
     *    summary="Restore Task",
     *  description="Restores a soft-deleted scheduled task.",
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="Task unique ID to restore",
     *    required=true,
     *    @OA\Schema(type="string", example="xxxx"),
     * ),
     *     @OA\Response(
     *         response=202,
     *         description="Restoration successful",
     *         @OA\JsonContent(
     *          @OA\Property(property="alertifyPayload", type="object",
     *             @OA\Property(property="message", type="string", example="task-manager restored succefully"),
     *             @OA\Property(property="theme", type="string",example="success"),
     *             @OA\Property(property="type", type="string",example="toast"),
     *          )
     *         )
     *     ),
     *   @OA\Response(
     *     response=404,
     *     description="Record not found",
     *      @OA\JsonContent(
     *           @OA\Property(property="errorPayload", type="object",
     *               @OA\Property(property="statusCode", type="integer", example=404 ),
     *               @OA\Property(property="message", type="string", example="The requested schedulermodel does not exist" )
     *           )
     *      )
     *   )
     * )
     */
    'PATCH task-manager/{id}'  => 'task-manager/toggle-delete',
];
