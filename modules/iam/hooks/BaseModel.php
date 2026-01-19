<?php
namespace iam\hooks;

/**
 * This is the base model class for iam module.
 *
 * @OA\Info(
 *     description="Identity & Access Management API documentation",
 *     version="1.0.0",
 *     title="Identity & Access Management",
 *     @OA\Contact(
 *         email="douglasdaggs@gmail.com",
 *         name="Ananda Douglas"
 *     )
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\OpenApi(security={{"bearerAuth":{}}})
 */
class BaseModel extends \helpers\ActiveRecord
{
    // Add any common functionality for your models here
}
