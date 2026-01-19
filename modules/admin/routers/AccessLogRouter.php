<?php
return [
    /**
     * @OA\Get(path="/admin/logs/access",
     *   summary="Get Logs",
     *   description="Returns a list of Access logs",
     *   tags={"Access Logs"},
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
     *     description="Global search query to filter logs",
     *     @OA\Schema(type="string", example="login")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Data Payload",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object",
     *              @OA\Property(property="data", type="array", @OA\Items( type="object",
     *              @OA\Property(property="access_id", type="integer", title="Access Id", description="No description", example="xxx-xxx"),
     *              @OA\Property(property="user", type="string", title="User ", description="User who accessed", example="System Admin"),
     *              @OA\Property(property="description", type="string", title="Description", description="Show what action was performed", example="Visited page: /admin/dashboard"),
     *              @OA\Property(property="ip_address", type="string", title="Ip Address", description="IP address of the user", example="192.168.1.1"),
     *              @OA\Property(property="user_agent", type="string", title="User Agent", description="Browser or device information of the user", example="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3"),
     *              @OA\Property(property="access_time", type="string", title="Access Time", description="Timestamp of when the access occurred", example="2024-04-27T12:34:56Z"),
     *                  )
     *              ),
     *              @OA\Property(property="countOnPage", type="integer", example="25"),
     *              @OA\Property(property="totalCount", type="integer",example="50"),
     *              @OA\Property(property="perPage", type="integer",example="25"),
     *              @OA\Property(property="totalPages", type="integer",example="2"),
     *              @OA\Property(property="currentPage", type="integer",example="1"),
     *              @OA\Property(property="paginationLinks", type="object",
     *                  @OA\Property(property="first", type="string",example="/v2/admin/logs/access?page=1&per-page=25"),
     *                  @OA\Property(property="previous", type="string",example="/v2/admin/logs/access?page=1&per-page=25"),
     *                  @OA\Property(property="self", type="string",example="/v2/admin/logs/access?page=1&per-page=25"),
     *                  @OA\Property(property="next", type="string",example="/v2/admin/logs/access?page=1&per-page=25"),
     *                  @OA\Property(property="last", type="string",example="/v2/admin/logs/access?page=1&per-page=25"),
     *              ),
     *          )
     *      )
     *   ),
     * )
     */
    'GET logs/access'         => 'access-log/index',

    /**
     * @OA\Get(path="/admin/logs/access/{id}",
     *   summary="Get Log ",
     *   description="Returns a single AccessLogModel model",
     *   tags={"Access Logs"},
     * @OA\Parameter(
     *    name="id",
     *    in="path",
     *    description="AccessLogModel unique ID to load",
     *    required=true,
     *    @OA\Schema(type="string", example="xxxx"),
     * ),
     *   @OA\Response(
     *     response=200,
     *     description="Data Payload",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object", ref="#/components/schemas/AccessLog")
     *      )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Record not found",
     *      @OA\JsonContent(
     *           @OA\Property(property="errorPayload", type="object",
     *               @OA\Property(property="statusCode", type="integer", example=404 ),
     *               @OA\Property(property="message", type="string", example="The requested log does not exist" )
     *           )
     *      )
     *   ),
     * )
     */
    'GET logs/access/{id}'     => 'access-log/view',

];
