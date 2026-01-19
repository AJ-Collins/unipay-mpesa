<?php
return [
    /**
     * @OA\Get(path="/admin/logs/error",
     *   summary="Get Errors",
     *  description="Returns a list of error logs",
     *   tags={"Error Logs"},
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
     *     description="Global search query to filter error logs",
     *     @OA\Schema(type="string", example="yii\web\HttpException:404")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Data Payload",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object",
     *              @OA\Property(property="data", type="array", @OA\Items( type="object",
     *              @OA\Property(property="id", type="bigint", title="Id", description="Error log id", example="1"),
     *              @OA\Property(property="level", type="integer", title="Level", description="Severity level of the error", example="Error"),
     *              @OA\Property(property="category", type="string", title="Category", description="Category of the error", example=" application"),
     *              @OA\Property(property="log_time", type="string", title="Log Time", description="Timestamp when the error was logged", example="2024-04-27T12:34:56Z"),
     *              @OA\Property(property="is_resolved", type="integer", title="Is Resolved", description="Indicates whether the error has been resolved", example="0"),
     *                  )
     *              ),
     *              @OA\Property(property="countOnPage", type="integer", example="25"),
     *              @OA\Property(property="totalCount", type="integer",example="50"),
     *              @OA\Property(property="perPage", type="integer",example="25"),
     *              @OA\Property(property="totalPages", type="integer",example="2"),
     *              @OA\Property(property="currentPage", type="integer",example="1"),
     *              @OA\Property(property="paginationLinks", type="object",
     *                  @OA\Property(property="first", type="string",example="/v2/admin/error-logs?page=1&per-page=25"),
     *                  @OA\Property(property="previous", type="string",example="/v2/admin/error-logs?page=1&per-page=25"),
     *                  @OA\Property(property="self", type="string",example="/v2/admin/error-logs?page=1&per-page=25"),
     *                  @OA\Property(property="next", type="string",example="/v2/admin/error-logs?page=1&per-page=25"),
     *                  @OA\Property(property="last", type="string",example="/v2/admin/error-logs?page=1&per-page=25"),
     *              ),
     *          )
     *      )
     *   ),
     * )
     */
    'GET logs/error'         => 'error-log/index',


    /**
     * @OA\Get(path="/admin/logs/error/{id}",
     *   summary="Get Error ",
     *   description="Returns a single error log",
     *   tags={"Error Logs"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="Log unique ID to load",
     *    required=true,
     *    @OA\Schema(type="string", example="xxxx"),
     * ),
     *   @OA\Response(
     *     response=200,
     *     description="Data Payload",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object", ref="#/components/schemas/LogModel")
     *      )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Record not found",
     *      @OA\JsonContent(
     *           @OA\Property(property="errorPayload", type="object",
     *               @OA\Property(property="statusCode", type="integer", example=404 ),
     *               @OA\Property(property="message", type="string", example="The requested error does not exist" )
     *           )
     *      )
     *   ),
     * )
     */
    'GET logs/error/{id}'     => 'error-log/view',

    /**
     * @OA\Post(
     *     path="/admin/logs/error/{id}",
     *     tags={"Error Logs"},
     *     summary="Resolve Error",
     *   description="Marks the specified error log as resolved",
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="Error Log unique ID to load",
     *    required=true,
     *    @OA\Schema(type="string", example="xxxx"),
     * ),
     *    @OA\Response(
     *       response=202,
     *       description="Data payload",
     *       @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object",
     *             @OA\Property(property="data", type="object",ref="#/components/schemas/LogModel"),
     *          ),
     *         @OA\Property(property="alertifyPayload", type="object",
     *            @OA\Property(property="message", type="string", example="Error resolved successfully"),
     *            @OA\Property(property="theme", type="string",example="success"),
     *            @OA\Property(property="type", type="string",example="alert"),
     *         )
     *       )
     *    ),
     *   @OA\Response(
     *     response=404,
     *     description="Record not found",
     *      @OA\JsonContent(
     *           @OA\Property(property="errorPayload", type="object",
     *               @OA\Property(property="statusCode", type="integer", example=404 ),
     *               @OA\Property(property="message", type="string", example="The requested logmodel does not exist" )
     *           )
     *      )
     *   ),
     *  @OA\Response(
     *    response=422,
     *    description="Data Validation Error",
     *    @OA\JsonContent(
     *       @OA\Property(property="errorPayload", type="object",
     *          @OA\Property(property="errors", type="object", ref="#/components/schemas/LogModel"),
     *       )
     *    )
     * )
     * )
     */
    'POST logs/error/{id}'     => 'error-log/resolve',

];
