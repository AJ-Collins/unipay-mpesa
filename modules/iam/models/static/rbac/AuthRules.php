<?php

namespace iam\models\static\rbac;

use iam\hooks\AuthConfigs;
use yii\helpers\Inflector;

/**
 * AuthRules - Business Rule Model for RBAC
 *
 * Manages business rules for Role-Based Access Control (RBAC) system.
 * Provides CRUD operations for authorization rules that can be attached to permissions.
 *
 * @property string $rule_name The name of the authorization rule
 * @property string $rule_title The title/display name of the rule (stored in database)
 * @property string $description Optional description of the rule's purpose
 *
 * @author Ananda Douglas <douglasdaggs@gmail.com>
 */
class AuthRules extends \iam\hooks\BaseModel
{
    /**
     * @var string The name of the authorization rule
     */
    public $rule_name;

    /**
     * Returns the name of the table associated with this model.
     * 
     * @return string The database table name for auth rules (typically 'auth_rule')
     */
    public static function tableName()
    {
        /* @var \yii\rbac\DbManager $authManager */
        $authManager = AuthConfigs::authManager();
        return $authManager->ruleTable;
    }

    /**
     * Defines the fields exposed by this model.
     * Maps internal attributes to external field names for API responses.
     * 
     * @return array Field mappings for serialization
     */
    public function fields()
    {
        return [
            'rule_name' => 'rule_title',
            'description'
        ];
    }

    /**
     * Defines validation rules for the model attributes.
     * 
     * @return array Validation rules including:
     *               - rule_name: required, max 64 characters, unique
     *               - description: optional, max 255 characters
     */
    public function rules()
    {
        return [
            [['rule_name'], 'required', 'message' => 'Rule name cannot be blank.'],
            [['rule_name'], 'string', 'max' => 64],
            [['description'], 'string', 'max' => 255],
            [['rule_name'], 'unique', 'targetAttribute' => 'rule_title', 'message' => '{value} has already been registered.'],
        ];
    }

    /**
     * Saves the model to the database.
     * Overrides parent method to map rule_name to rule_title before saving.
     * 
     * @param bool $runValidation Whether to perform validation before saving
     * @param array|null $attributeNames List of attributes to save, or null for all
     * @return bool True if save was successful, false otherwise
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if (!$this->validate()) {
            return false;
        }
        $this->rule_title = $this->rule_name;
        return parent::save($runValidation, $attributeNames);
    }

    public function dropdown($q = null)
    {
        return \yii\helpers\ArrayHelper::map(
            self::find()
                ->andWhere(self::ciLikeAny(['rule_title', 'name', 'description'], $q ?? ''))
                ->orderBy(['name' => SORT_ASC])
                ->limit(15)
                ->all(),
            function ($item) {
                return Inflector::camel2id($item->name, '_');
            },
            'rule_title'
        );
    }
}
