<?php
namespace unipay\hooks;

/**
 * This is the base model class for unipay module.
 *
 * @OA\Info(
 *     description="API documentation for unipay module",
 *     version="1.0.0",
 *     title="Unipay Module",
 *     @OA\Contact(
 *         email="douglasdaggs@gmail.com",
 *         name="Ananda Douglas"
 *     )
 * )
 */  

 /**
 * @OA\SecurityScheme(securityScheme="bearerAuth",type="http",scheme="bearer",bearerFormat="JWT")
 * @OA\OpenApi(security={{"bearerAuth":{}}})
 * */
class BaseModel extends \helpers\ActiveRecord
{
    
}
