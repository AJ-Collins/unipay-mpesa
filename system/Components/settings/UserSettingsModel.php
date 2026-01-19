<?php

namespace helpers\settings;

use Yii;
use helpers\ActiveRecord;
use helpers\migrations\UserSettingsMigration;

class UserSettingsModel extends ActiveRecord
{
    public $data;
    private static bool $_tableEnsured = false;
    public static function tableName()
    {
        static::ensureTableExistsOnce();

        return '{{%user_settings}}';
    }
    public function rules()
    {
        return [
            [['user_id', 'settings'], 'required']
        ];
    }
    
    /**
     * Get a single setting value
     */
    public function getSetting($category, $key, $default = null)
    {
        $settings = $this->getDecodedSettings();
        return $settings[$category][$key] ?? $default;
    }
    public function mergeWithDefaults($defaults = [], $removeObsolete = true)
    {
        $current = $this->getDecodedSettings();
        $merged = [];

        foreach ($defaults as $category => $fields) {
            foreach ($fields as $field) {
                $key = $field['key'];
                $merged[$category][$key] =
                    $current[$category][$key] ?? $field['default_value'];
            }
        }

        if (!$removeObsolete) {
            // keep any extra user-defined keys that are no longer in defaults
            foreach ($current as $category => $keys) {
                foreach ($keys as $k => $v) {
                    if (!isset($merged[$category][$k])) {
                        $merged[$category][$k] = $v;
                    }
                }
            }
        }

        $this->settings = json_encode($merged, JSON_PRETTY_PRINT);
        return $this->save(false);
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
            (new UserSettingsMigration())->safeUp();
        }
    }

    /**
     * Returns raw table name without Yii's {{%}} placeholder
     */
    protected static function tableNameRaw(): string
    {
        return str_replace(['{{%', '}}'], ['', ''], '{{%user_settings}}');
    }
}
