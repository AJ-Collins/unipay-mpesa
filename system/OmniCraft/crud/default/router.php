<?php
$item =$generator->getControllerID();
$module = (explode('\\',$generator->controllerClass))[0].'/';
$items=\yii\helpers\Inflector::pluralize($item);
$model = \yii\helpers\StringHelper::basename($generator->modelClass);
echo "<?php\n"; 
?>
return [
/**
 * @OA\Get(path="/<?=$module.$items;?>",
 *   summary="Get  <?= $items ?>  ",
 *   description="Returns a list of <?= $model ?>  models",
 *   tags={"<?=$model?>"},
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
 *     description="Global search query to filter <?= $items ?>",
 *     @OA\Schema(type="string", example="admin")
 *   ),
 *   @OA\Response(
 *     response=200,
 *     description="<?=$items?> Data Payload",
 *      @OA\JsonContent(
 *          @OA\Property(property="dataPayload", type="object",
 *              @OA\Property(property="data", type="array", @OA\Items( type="object",
    <?php foreach ($generator->getTableSchema()->columns as $data): ?>
    *              @OA\Property(property="<?=$data->name?>", type="<?=$data->type?>", title="<?=\yii\helpers\Inflector::camel2words($data->name)?>", description="No description", example="xxx-xxx"),
    <?php endforeach; ?>
 *                  )
 *              ),
 *              @OA\Property(property="countOnPage", type="integer", example="25"),
 *              @OA\Property(property="totalCount", type="integer",example="50"),
 *              @OA\Property(property="perPage", type="integer",example="25"),
 *              @OA\Property(property="totalPages", type="integer",example="2"),
 *              @OA\Property(property="currentPage", type="integer",example="1"),
 *              @OA\Property(property="paginationLinks", type="object",
 *                  @OA\Property(property="first", type="string",example="/<?=$_ENV['APP_VERSION']?>/<?=$module.$items?>?page=1&per-page=25"),
 *                  @OA\Property(property="previous", type="string",example="/<?=$_ENV['APP_VERSION']?>/<?=$module.$items?>?page=1&per-page=25"),
 *                  @OA\Property(property="self", type="string",example="/<?=$_ENV['APP_VERSION']?>/<?=$module.$items?>?page=1&per-page=25"),
 *                  @OA\Property(property="next", type="string",example="/<?=$_ENV['APP_VERSION']?>/<?=$module.$items?>?page=1&per-page=25"),
 *                  @OA\Property(property="last", type="string",example="/<?=$_ENV['APP_VERSION']?>/<?=$module.$items?>?page=1&per-page=25"),
 *              ),
 *          )
 *      )
 *   ),
 * )
 */
'GET <?=$items?>'         => '<?=$item?>/index',

/**
 * @OA\Post(
 * path="/<?=$module.$item?>",
 * summary="Create <?=$model?> ",
 * description="Creates a new <?=$model?> model",
 * tags={"<?=$model?>"},
 * @OA\RequestBody(
 *    required=true,
 *    description="Fill in <?=$item?> data",
 *    @OA\JsonContent(
 *       required={<?=$generator->generateRequiredRules()?>},
 *       ref="#/components/schemas/<?=$model?>",
 *    ),
 * ),
 * @OA\Response(
 *    response=201,
 *    description="Data payload",
 *    @OA\JsonContent(
 *       @OA\Property(property="dataPayload", type="object",
 *          @OA\Property(property="data", type="object",ref="#/components/schemas/<?=$model?>"),
 *       ),
 *       @OA\Property(property="alertifyPayload", type="object",
 *          @OA\Property(property="message", type="string", example="<?=$item?> created succefully"),
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
 *          @OA\Property(property="errors", type="object", ref="#/components/schemas/<?=$model?>"),
 *       )
 *    )
 * )
 *),
 */
'POST <?=$item?>'         => '<?=$item?>/create',

/**
 * @OA\Get(path="/<?=$module.$item;?>/{id}",
 *   summary="Get <?=$model?> ",
 *   description="Returns a single <?=$model?> model",
 *   tags={"<?=$model?>"},
 * @OA\Parameter(
 *    name="id",
 *    in="path",
 *    description="<?= $model ?> unique ID to load",
 *    required=true,
 *    @OA\Schema(type="string", example="xxxx"),
 * ),
 *   @OA\Response(
 *     response=200,
 *     description="Data Payload",
 *      @OA\JsonContent(
 *          @OA\Property(property="dataPayload", type="object", ref="#/components/schemas/<?=$model?>")
 *      )
 *   ),
 *   @OA\Response(
 *     response=404,
 *     description="Record not found",
 *      @OA\JsonContent(
 *           @OA\Property(property="errorPayload", type="object",
 *               @OA\Property(property="statusCode", type="integer", example=404 ),
 *               @OA\Property(property="message", type="string", example="The requested <?=strtolower($model);?> does not exist" )
 *           )
 *      )
 *   ),
 * )
 */
'GET <?=$item?>/{id}'     => '<?=$item?>/view',

/**
* @OA\Put(
*     path="/<?=$module.$item?>/{id}",
*     tags={"<?=$model?>"},
*     summary="Update <?=$model?>",
*     description="Updates an existing <?=$model?> model.",
* @OA\Parameter(
 *    name="id",
 *    in="path",
 *    description="<?= $model ?> unique ID to load",
 *    required=true,
 *    @OA\Schema(type="string", example="xxxx"),
 * ),
*     @OA\RequestBody(
*        required=true,
*        description="Finds the <?=$model?> model to be updated based on its primary key value",
*        @OA\JsonContent(
*           ref="#/components/schemas/<?=$model?>",
*        ),
*     ),
*    @OA\Response(
*       response=202,
*       description="Data payload",
*       @OA\JsonContent(
*          @OA\Property(property="dataPayload", type="object",
*             @OA\Property(property="data", type="object",ref="#/components/schemas/<?=$model?>"),
*          ),
 *         @OA\Property(property="alertifyPayload", type="object",
 *            @OA\Property(property="message", type="string", example="<?=$item?> updated succefully"),
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
 *               @OA\Property(property="message", type="string", example="The requested <?=strtolower($model);?> does not exist" )
 *           )
 *      )
 *   ),
 *  @OA\Response(
 *    response=422,
 *    description="Data Validation Error",
 *    @OA\JsonContent(
 *       @OA\Property(property="errorPayload", type="object",
 *          @OA\Property(property="errors", type="object", ref="#/components/schemas/<?=$model?>"),
 *       )
 *    )
 * )
* )
*/
'PUT <?=$item?>/{id}'     => '<?=$item?>/update',

/**
* @OA\Delete(path="/<?=$module.$item?>/{id}",
*    tags={"<?=$model?>"},
*    summary="Delete <?=$model?> ",
*    description="Deletes an existing <?=$model?> model.",
* @OA\Parameter(
*    name="id",
*    in="path",
*    description="<?= $model ?> unique ID to delete",
*    required=true,
*    @OA\Schema(type="string", example="xxxx"),
* ),
*     @OA\Response(
*         response=202,
*         description="Deletion successful",
*         @OA\JsonContent(
 *         @OA\Property(property="alertifyPayload", type="object",
 *            @OA\Property(property="message", type="string", example="<?=$item?> deleted succefully"),
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
 *               @OA\Property(property="message", type="string", example="The requested <?=strtolower($model);?> does not exist" )
 *           )
 *      )
 *   )
* )
*/
'DELETE <?=$item?>/{id}'  => '<?=$item?>/toggle-delete',

/**
* @OA\Patch(path="/<?=$module.$item?>/{id}",
*    tags={"<?=$model?>"},
*    summary="Restore <?=$model?> ",
*    description="Restores a deleted <?=$model?> model.",
* @OA\Parameter(
*    name="id",
*    in="path",
*    description="<?= $model ?> unique ID to restore",
*    required=true,
*    @OA\Schema(type="string", example="xxxx"),
* ),
*     @OA\Response(
*         response=202,
*         description="Restoration successful",
*         @OA\JsonContent(
 *          @OA\Property(property="alertifyPayload", type="object",
 *             @OA\Property(property="message", type="string", example="<?=$item?> restored succefully"),
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
 *               @OA\Property(property="message", type="string", example="The requested <?=strtolower($model);?> does not exist" )
 *           )
 *      )
 *   )
* )
*/
'PATCH <?=$item?>/{id}'  => '<?=$item?>/toggle-delete',
];