<?php
return [
    /**
     * @OA\Get(path="/admin/audit/trail",
     *   summary="Get all audit records",
     *   description="Returns a list of audit records",
     *   tags={"Audit Trail"},
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
     *     description="Global search query to filter audit records",
     *     @OA\Schema(type="string", example="admin")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Data payload",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object",
     *              @OA\Property(property="data", type="array",@OA\Items(ref="#/components/schemas/Audit Trail List")),
     *              @OA\Property(property="countOnPage", type="integer", example="25"),
     *              @OA\Property(property="totalCount", type="integer",example="50"),
     *              @OA\Property(property="perPage", type="integer",example="25"),
     *              @OA\Property(property="totalPages", type="integer",example="2"),
     *              @OA\Property(property="currentPage", type="integer",example="1"),
     *              @OA\Property(property="paginationLinks", type="object",
     *                  @OA\Property(property="first", type="string",example="v2/admin/audit-trails?page=1&per-page=25"),
     *                  @OA\Property(property="previous", type="string",example="v2/admin/audit-trails?page=1&per-page=25"),
     *                  @OA\Property(property="self", type="string",example="v2/admin/audit-trails?page=1&per-page=25"),
     *                  @OA\Property(property="next", type="string",example="v2/admin/audit-trails?page=1&per-page=25"),
     *                  @OA\Property(property="last", type="string",example="v2/admin/audit-trails?page=1&per-page=25"),
     *              ),
     *          )
     *      )
     *   ),
     * )
     */
    'GET audit/trail'         => 'audit-trail/index',

    /**
     * @OA\Get(path="/admin/audit/trail/{uid}",
     *   summary="Get Audit record ",
     *   description="Returns a single audit record",
     *   tags={"Audit Trail"},
     * @OA\Parameter(
     *    name="uid",
     *    in="path",
     *    description="Audit record unique ID to load",
     *    required=true,
     *    @OA\Schema(type="string", example="xxxx"),
     * ),
     *   @OA\Response(
     *     response=200,
     *     description="Returns a single audit record.",
     *      @OA\JsonContent(
     *          @OA\Property(property="dataPayload", type="object", ref="#/components/schemas/Audit Trail"))
     *      ),
     *   @OA\Response(
     *     response=404,
     *     description="Record not found",
     *      @OA\JsonContent(
     *           @OA\Property(property="errorPayload", type="object",
     *               @OA\Property(property="statusCode", type="integer", example=404 ),
     *               @OA\Property(property="message", type="string", example="The requested auditmodel does not exist" )
     *           )
     *      )
     *   ),
     * )
     */
    'GET audit/trail/{uid}'     => 'audit-trail/view',

    /**
     * @OA\Delete(path="/admin/audit/trail/{uid}",
     *    tags={"Audit Trail"},
     *    summary="Delete Audit record ",
     *    description="Deletes an existing audit record.",
     * @OA\Parameter(
     *    name="uid",
     *    in="path",
     *    description="Audit record unique ID to delete",
     *    required=true,
     *    @OA\Schema(type="string", example="xxxx"),
     * ),
     *     @OA\Response(
     *         response=202,
     *         description="Deletion successful",
     *         @OA\JsonContent(
     *         @OA\Property(property="alertifyPayload", type="object",
     *            @OA\Property(property="message", type="string", example="audit-trail deleted succefully"),
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
     *               @OA\Property(property="message", type="string", example="The requested auditmodel does not exist" )
     *           )
     *      )
     *   )
     * )
     */
    'DELETE audit/trail/{uid}'  => 'audit-trail/toggle-delete',

    /**
     * @OA\Patch(path="/admin/audit/trail/{uid}",
     *    tags={"Audit Trail"},
     *    summary="Restore Audit record ",
     *    description="Restores a deleted audit record.",
     * @OA\Parameter(
     *    name="uid",
     *    in="path",
     *    description="Audit record unique ID to restore",
     *    required=true,
     *    @OA\Schema(type="string", example="xxxx"),
     * ),
     *     @OA\Response(
     *         response=202,
     *         description="Restoration successful",
     *         @OA\JsonContent(
     *          @OA\Property(property="alertifyPayload", type="object",
     *             @OA\Property(property="message", type="string", example="audit-trail restored succefully"),
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
     *               @OA\Property(property="message", type="string", example="The requested auditmodel does not exist" )
     *           )
     *      )
     *   )
     * )
     */
    'PATCH audit/trail/{uid}'  => 'audit-trail/toggle-delete',
];
