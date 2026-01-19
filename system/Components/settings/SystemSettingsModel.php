<?php

namespace helpers\settings;

use Yii;
use helpers\ActiveRecord;
use helpers\migrations\SystemSettingsMigration;

class SystemSettingsModel extends ActiveRecord
{
    private static bool $_tableEnsured = false;
    public static function tableName()
    {
        static::ensureTableExistsOnce();

        return '{{%system_settings}}';
    }
    public function rules()
    {
        return [
            [['key', 'disposition', 'default_value'], 'required'],
            [['current_value', 'default_value'], 'string'],
            [['key'], 'string', 'max' => 100],
            [['category'], 'string', 'max' => 20],
            [['key'], 'unique'],
        ];
    }

    protected static function ensureTableExistsOnce()
    {
        if (!static::$_tableEnsured) {
            static::$_tableEnsured = true;
            static::ensureTableExists();
        }
    }

    /**
     * Actual table existence check and creation
     */
    protected static function ensureTableExists()
    {
        $db = Yii::$app->db;
        $tableName = static::tableNameRaw();

        if ($db->schema->getTableSchema($tableName) === null) {
            (new SystemSettingsMigration())->safeUp();
        }
    }

    /**
     * Returns raw table name without Yii's {{%}} placeholder
     */
    protected static function tableNameRaw(): string
    {
        return str_replace(['{{%', '}}'], ['', ''], '{{%system_settings}}');
    }
}
