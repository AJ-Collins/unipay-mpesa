<?php

namespace iam\models\static\rbac;

use Yii;
use helpers\data\ActiveDataProvider;

/**
 * AuthRuleSearch provides search functionality for authentication rules.
 * 
 * This class handles searching and filtering of RBAC (Role-Based Access Control) rules
 * in the IAM (Identity and Access Management) module.
 *
 * @author ananda douglas <douglasdaggs@gmail.com>
 */
class AuthRuleSearch extends AuthRules
{
    /**
     * Defines validation rules for the search model attributes.
     *
     * @return array Validation rules
     */
    public function rules()
    {
        return [
            [['name', 'description', 'module', 'q'], 'safe']
        ];
    }

    /**
     * Searches for authentication rules based on provided parameters.
     * 
     * This method creates a dynamic ActiveRecord model for the auth rule table,
     * applies search filters or general query search, and returns an ActiveDataProvider
     * with the filtered results.
     *
     * @param array $params Search parameters containing filter values
     * @return ActiveDataProvider Data provider containing filtered auth rules
     */
    public function search($params)
    {
        // Ensure params are properly formatted with the class name as key
        $params[(new \ReflectionClass($this))->getShortName()] = $params;
        
        // Create an anonymous ActiveRecord class to represent the auth rule table
        $model = new class extends AuthRules {
            /**
             * Returns the name of the database table for auth rules.
             *
             * @return string Table name from auth manager configuration
             */
            public static function tableName()
            {
                return (\iam\hooks\AuthConfigs::authManager())->ruleTable;
            }
            
            /**
             * Defines the fields to be included in the response.
             *
             * @return array Field definitions with transformations
             */
            public function fields()
            {
                return [
                    'rule_id' => function ($model) {
                        return $model->name;
                    },
                    'rule_name' => 'rule_title',
                    'description',
                    'lastUpdated' => function ($model) {
                        return Yii::$app->settings->dateTime($model->updated_at);
                    },
                ];
            }
        };
        
        // Initialize the query
        $query = $model::find();
        
        // Create data provider for pagination and sorting
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        
        // Load search parameters into the model
        $this->load($params);

        // If validation fails, return empty results
        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        // Handle general search query (searches across multiple fields)
        if (!empty($this->q)) {
            $query->andFilterWhere(
                self::ciLikeAny(['name', 'rule_title', 'description', 'module'], $this->q)
            );
            return $dataProvider;
        }
        
        // Handle individual field filters (case-insensitive LIKE queries)
        $query->andFilterWhere(self::ciLike('name', $this->name ?? ''))
            ->andFilterWhere(self::ciLike('rule_title', $this->rule_title ?? ''))
            ->andFilterWhere(self::ciLike('description', $this->description ?? ''));
            
        return $dataProvider;
    }
}
