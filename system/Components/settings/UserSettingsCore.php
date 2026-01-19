<?php

namespace helpers\settings;

use Yii;
use yii\helpers\ArrayHelper;

class UserSettingsCore extends \yii\base\Model
{
    private $_attributes = [];
    public $settingKeys = [];
    public $settings; // instance of UserSettingsModel

    public function init()
    {
        parent::init();
        $userId = Yii::$app->user->id;

        $this->settings = UserSettingsModel::findOne(['user_id' => $userId]);
        if (!$this->settings) {
            $this->settings = new UserSettingsModel([
                'user_id' => $userId,
                'data' => [],
            ]);
            $this->settings->save(false);
        }

        // load and sync keys
        $this->settingKeys = $this->updateKeys();
        foreach ($this->settingKeys as $item) {
            $this->_attributes[$item['key']] = $item['current_value'] ?? $item['default_value'];
        }
    }

    public function __get($name)
    {
        return $this->_attributes[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->_attributes[$name] = $value;
    }

    public function attributes()
    {
        return array_column($this->settingKeys, 'key');
    }

    public function attributeLabels()
    {
        return ArrayHelper::map($this->settingKeys, 'key', 'label');
    }

    public function rules()
    {
        $rules = [];
        foreach ($this->settingKeys as $item) {
            $key = $item['key'];

            // default required
            $rules[] = [[$key], 'required'];

            // merge custom validations
            if (!empty($item['validations']) && is_array($item['validations'])) {
                foreach ($item['validations'] as $validation) {
                    if (is_array($validation)) {
                        $rule = $validation;
                        $rule[0] = (array)$key;
                        $rules[] = $rule;
                    }
                }
            }
        }
        return $rules;
    }

    /**
     * Add/remove missing keys, and persist back to DB
     */
    public function updateKeys()
    {
        $data = $this->getDecodedSettings();
        $category = $this->getCategory();

        $existing = $data[$category] ?? [];
        $exist = ArrayHelper::index($existing, 'key');
        $list = ArrayHelper::index($this->availableKeys(), 'key');

        $toAdd = array_diff_key($list, $exist);
        $toRemove = array_diff_key($exist, $list);

        // add missing keys
        foreach ($toAdd as $key => $config) {
            $data[$category][$key] = [
                'key' => $config['key'],
                'current_value' => $config['default_value'] ?? null,
            ];
        }

        // remove extra keys
        foreach ($toRemove as $key => $config) {
            unset($data[$category][$key]);
        }

        // persist back to db
        $this->settings->data = json_encode($data, JSON_PRETTY_PRINT);
        $this->settings->save(false);

        return $this->getSettingsByCategory();
    }

    public function getCategory()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function getSettingsByCategory()
    {
        $settings = $this->getDecodedSettings()[$this->getCategory()] ?? [];
        $keys = ArrayHelper::index($this->availableKeys(), 'key');

        return array_map(function ($setting) use ($keys) {
            $key = $setting['key'] ?? null;
            if (!$key) {
                return $setting;
            }
            $setting['label'] = $keys[$key]['label'] ?? \yii\helpers\Inflector::camel2words(\yii\helpers\Inflector::id2camel($key));
            $setting['input_type'] = $keys[$key]['input_type'] ?? 'textInput';
            if (isset($keys[$key]['validations'])) {
                $setting['validations'] = $keys[$key]['validations'];
            }
            if (isset($keys[$key]['input_preload'])) {
                $setting['input_preload'] = $keys[$key]['input_preload'];
            }
            return $setting;
        }, $settings);
    }

    /**
     * Return settings as array grouped by category
     */
    public function getDecodedSettings(): array
    {
        $data = $this->settings->data;
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        if (!is_array($data)) {
            $data = [];
        }
        if (!isset($data[$this->getCategory()])) {
            $data[$this->getCategory()] = [];
        }
        return $data;
    }

    public function availableKeys()
    {
        return []; // must be overridden in subclass
    }
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            return false;
        }

        // Decode existing settings
        $data = $this->getDecodedSettings();

        // Update only this category
        $data[$this->getCategory()] = [];
        foreach ($this->_attributes as $key => $value) {
            $data[$this->getCategory()][] = [
                'key' => $key,
                'current_value' => $value,
            ];
        }
        // Serialize back into JSON
        $this->settings->data = json_encode($data, JSON_PRETTY_PRINT);

        return $this->settings->save(false);
    }
}
