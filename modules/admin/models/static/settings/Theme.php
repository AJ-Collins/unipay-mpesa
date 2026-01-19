<?php

namespace admin\models\static\settings;

use helpers\settings\SystemSettingsCore;
/**
 * @OA\Schema(
 *  schema="Theme & Appearance",
 *  @OA\Property(property="defaultPageSize", type="integer", title="Default Page Size", description="Default number of items per page", example=25,),
 *  @OA\Property(property="pageSizeLimit", type="integer", title="Page Size Limit", description="Maximum number of items per page", example=100,),
 * )
 */

class Theme extends SystemSettingsCore
{
    protected function defineSettings(): array
    {
        return [
            'defaultPageSize' => [
                'default'     => 25,
                'validations' => [
                    [['defaultPageSize'], 'integer'],
                ],
            ],
            'pageSizeLimit' => [
                'default'     => 100,
                'validations' => [
                    [['pageSizeLimit'], 'integer'],
                ],
            ],

        ];
    }
}
