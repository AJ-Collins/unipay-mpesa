<?php

namespace helpers\settings;

use yii\base\Component;

class AppSettings extends Component
{
    private ?SystemSettingsModel $instance = null;

    public function init()
    {
        parent::init();
        $this->instance = new SystemSettingsModel();
    }

    /**
     * Magic getter: $settings->api_key
     */
    public function __get($name)
    {
        return $this->instance->__get($name) ?? null;
    }

    /**
     * Magic setter: $settings->business_name = 'New Name';
     */
    public function __set($name, $value)
    {
        $this->instance->__set($name, $value);
    }

    /**
     * Check if setting exists
     */
    public function __isset($name)
    {
        return isset($this->instance->$name);
    }

    /**
     * Get all settings as array (decrypted)
     */
    public function all(): array
    {
        return $this->instance->toArray();
    }
}