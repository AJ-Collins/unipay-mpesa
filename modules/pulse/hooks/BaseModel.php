<?php
namespace pulse\hooks;

/**
 * This is the base model class for pulse module.
 *
 * @OA\Info(
 *     description="API documentation for pulse module",
 *     version="1.0.0",
 *     title="pulse Module",
 *     @OA\Contact(
 *         email="douglasdaggs@gmail.com",
 *         name="Ananda Douglas"
 *     )
 * )
 */  

 /**
 * @OA\SecurityScheme(securityScheme="bearerAuth",type="http",scheme="bearer",bearerFormat="JWT")
 * @OA\SecurityScheme(securityScheme="cookieAuth",type="http",in="cookie",scheme="bearer",name="refresh-token")
 * @OA\OpenApi(security={{"bearerAuth":{}}})
 * */

/**
 * @OA\Tag(
 *     name="PULSE",
 *     description="Endpoints for the PULSE module"
 * )
 */

/**
 * @OA\Get(path="/about",
 *   summary="Module Info. ",
 *   tags={"PULSE"},
 *   security={{}},
 *   @OA\Response(
 *     response=200,
 *     description="success",
 *      @OA\JsonContent(
 *          @OA\Property(property="data", type="array",@OA\Items(ref="#/components/schemas/About")),
 *          
 *      )
 *   ),
 * )
 */

/**
 *@OA\Schema(
 *  schema="About",
 *  @OA\Property(property="id", type="string",title="Module ID", example="PULSE"),
 *  @OA\Property(property="name", type="string",title="Module Name", example="PULSE Module"),
 * )
 */
class BaseModel extends \helpers\ActiveRecord
{
    
}
