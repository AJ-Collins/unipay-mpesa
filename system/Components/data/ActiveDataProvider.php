<?php

namespace helpers\data;

use yii\db\ActiveQuery;
use yii\data\ActiveDataProvider as DataProvider;

class ActiveDataProvider extends DataProvider
{
    public function __construct($config = [])
    {
        // Merge default pagination config with user-provided config (if any)
        $config['pagination'] = $config['pagination'] ?? [
            'defaultPageSize' => \Yii::$app->settings->get('defaultPageSize') === null ? 25 : \Yii::$app->settings->get('defaultPageSize'),
            'pageSizeLimit' => [1, \Yii::$app->settings->get('pageSizeLimit') === null ? 100 : \Yii::$app->settings->get('pageSizeLimit')],
        ];
        // Merge default sort config with user-provided config (if any)
        if (isset($config['query']) && $config['query'] instanceof ActiveQuery) {
            $modelClass = $config['query']->modelClass;
            if (isset($modelClass) && is_subclass_of($modelClass, \helpers\ActiveRecord::class)) {
                $tableSchema = $modelClass::getTableSchema();
                if (isset($tableSchema->columns['created_at'])) {
                    $config['sort'] = $config['sort'] ?? [
                        'defaultOrder' => ['created_at' => SORT_DESC],
                    ];
                }
            }
        }

        parent::__construct($config);
    }
}
