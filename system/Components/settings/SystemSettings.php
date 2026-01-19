<?php

namespace helpers\settings;

use Yii;
use yii\base\Component;

class SystemSettings extends Component
{
    use \helpers\traits\Keygen;

    private $cache = null;
    private $cacheKey = 'app_settings_cache'; 
    public $cacheDuration = 3600; // 1 hour
    private $data = [];

    public function init()
    {
        parent::init();
        $this->loadSettings();
    }

    /**
     * Get a setting value by key (case-insensitive, but stored uppercase internally)
     * Automatically decrypts if 'salt' exists and value is encrypted.
     *
     * @param string $key The setting key (e.g., 'business_name' or 'BUSINESS_NAME')
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        $this->loadSettings();

        $key = strtoupper($key); // Normalize to uppercase for lookup

        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        Yii::warning("Setting key '$key' not found", __METHOD__);
        return $default;
    }
    
    public function DateTime($timeStamp)
    {
       return  Yii::$app->formatter->asDatetime($timeStamp, 'yyyy-MM-dd HH:mm:ss');
    }

    /**
     * Load all settings with caching and automatic decryption support
     */
    private function loadSettings()
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $cache = Yii::$app->cache;

        // Try to load from cache
        if ($cache && $cache->exists($this->cacheKey)) {
            $this->cache = $cache->get($this->cacheKey);
            if ($this->cache !== false) {
                $this->rebuildDataMap();
                return $this->cache;
            }
        }

        // Load fresh from database
        $items = SystemSettingsModel::find()
            ->asArray()
            ->all();

        if (empty($items)) {
            $this->cache = [];
            $this->data = [];
            return $this->cache;
        }

        $this->cache = $items;
        $this->data = [];

        foreach ($items as $item) {
            $key = strtoupper($item['key']);
            $value = $item['current_value'] ?? $item['default_value'];

            // Decrypt if salt exists and value is base64-encoded encrypted data
            if (!empty($item['salt']) && !empty($value)) {
                try {
                    $decoded = base64_decode($value, true);
                    if ($decoded !== false) {
                        $decrypted = Yii::$app->security->decryptByKey($decoded, $item['salt']);
                        if ($decrypted !== false) {
                            $value = $decrypted;
                        }
                    }
                } catch (\Exception $e) {
                    Yii::error("Failed to decrypt setting '{$key}': " . $e->getMessage());
                    $value  = null;
                    // Fallback to raw value if decryption fails
                }
            }

            $this->data[$key] = $value;
        }

        // Cache the raw items (not decrypted values — for consistency)
        if ($cache) {
            $cache->set($this->cacheKey, $this->cache, $this->cacheDuration);
        }

        return $this->cache;
    }

    /**
     * Rebuild $this->data map from cached raw items
     */
    private function rebuildDataMap()
    {
        $this->data = [];
        foreach ($this->cache as $item) {
            $key = strtoupper($item['key']);
            $value = $item['current_value'] ?? $item['default_value'];

            if (!empty($item['salt']) && !empty($value)) {
                try {
                    $decoded = base64_decode($value, true);
                    if ($decoded !== false) {
                        $decrypted = Yii::$app->security->decryptByKey($decoded, $item['salt']);
                        if ($decrypted !== false) {
                            $value = $decrypted;
                        }
                    }
                } catch (\Exception $e) {
                    Yii::error("Cache rebuild: Failed to decrypt '{$key}'");
                }
            }

            $this->data[$key] = $value;
        }
    }

    /**
     * Invalidate cache (call after updating any setting)
     */
    public function invalidateCache()
    {
        $this->cache = null;
        $this->data = [];

        if (Yii::$app->cache) {
            Yii::$app->cache->delete($this->cacheKey);
        }

        // Optionally reload immediately
        $this->loadSettings();
    }

    /**
     * Optional: Get all settings as array (decrypted)
     */
    public function all(): array
    {
        $this->loadSettings();
        return $this->data;
    }
}