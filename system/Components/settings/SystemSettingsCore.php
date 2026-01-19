<?php

namespace helpers\settings;

use Yii;
use yii\base\Model;
use yii\caching\TagDependency;

abstract class SystemSettingsCore extends Model
{
    use \helpers\traits\Keygen;
    private array $cache = [];
    private array $seed = [];
    private ?string $secretKeyCache = null;
    public $salt_hash = null;
    // This will be used for tag-based cache invalidation
    private string $cachePrefix;

    abstract protected function defineSettings(): array;

    public function __construct(array $seedValues = [], $config = [])
    {
        $this->seed = $seedValues;
        parent::__construct($config);
    }

    public function init()
    {
        parent::init();

        $tenantId = Yii::$app->tenant->id ?? 'default';
        $this->cachePrefix = 'system-settings:' . $tenantId . ':' . $this->getCategory();

        $this->loadValues();
    }

    public function __get($name)
    {
        return $this->cache[$name] ?? null;
    }

    public function __set($name, $value)
    {
        if (!array_key_exists($name, $this->cache)) {
            throw new \InvalidArgumentException("Setting '{$name}' not defined");
        }
        $this->cache[$name] = $value;
    }

    public function attributes(): array
    {
        return array_keys($this->cache);
    }

    public function rules(): array
    {
        $rules = [];
        foreach ($this->defineSettings() as $key => $def) {
            $rules[] = [[$key], 'required'];
            if (!empty($def['validations'] ?? [])) {
                foreach ($def['validations'] as $rule) {
                    $rule[0] = [$key];
                    $rules[] = $rule;
                }
            }
        }
        return $rules;
    }

    private function loadValues(): void
    {
        $cache = Yii::$app->cache;

        $this->cache = $cache->getOrSet(
            $this->cachePrefix,
            function () {
                $defined = $this->defineSettings();
                $category = $this->getCategory();

                // Sync DB for missing keys
                $this->syncDatabase();

                $rows = SystemSettingsModel::find()
                    ->where(['category' => $category])
                    ->indexBy('key')
                    ->asArray()
                    ->all();

                $values = [];
                foreach ($defined as $key => $def) {
                    $row = $rows[$key] ?? null;
                    if ($row === null) {
                        $values[$key] = $def['default'] ?? '';
                    } else {
                        $values[$key] = $row['current_value'] ?? $row['default_value'];
                        if (!empty($def['encrypted']) && $def['encrypted'] === true) {
                            $this->salt_hash = $row['salt'];
                            $values[$key] = $this->decrypt($values[$key], $this->salt_hash);
                        }
                    }
                }

                return $values;
            },
            Yii::$app->params['CacheDuration']['System'] ?? 86400,
            new TagDependency(['tags' => $this->cachePrefix])
        );
    }

    private function syncDatabase(): void
    {
        $defined = $this->defineSettings();
        $category = $this->getCategory();

        $existing = SystemSettingsModel::find()
            ->where(['category' => $category])
            ->indexBy('key')
            ->all();

        $definedKeys = array_keys($defined);

        // Add missing keys
        $toAdd = array_diff($definedKeys, array_keys($existing));
        if ($toAdd) {
            $maxDisp = (int)SystemSettingsModel::find()
                ->where(['category' => $category])
                ->max('disposition');

            foreach ($toAdd as $key) {
                $model = new SystemSettingsModel();
                $def = $defined[$key];
                if (!empty($def['encrypted']) && $def['encrypted'] === true) {
                    $this->salt_hash = $this->secretKey();
                    $def['default'] = $this->encrypt($def['default'], $this->salt_hash);
                    if (!empty($this->seed[$key])) {
                        $this->seed[$key] = $this->encrypt($this->seed[$key], $this->salt_hash);
                    }
                    $model->salt          = $this->salt_hash;
                }

                $maxDisp++;
                $model->key           = $key;
                $model->category      = $category;
                $model->disposition   = $def['disposition'] ?? $maxDisp;
                $model->current_value = $this->seed[$key] ?? null;
                $model->default_value = $def['default'] ?? '';
                $model->save(false);
            }
        }
        // Remove deleted keys
        $toRemove = array_diff(array_keys($existing), $definedKeys);
        if ($toRemove) {
            SystemSettingsModel::deleteAll(['key' => $toRemove, 'category' => $category]);
        }
    }

    public function saveAll(): bool
    {
        foreach ($this->attributes() as $key) {
            $value = $this->$key;
            $def = $this->defineSettings()[$key];
            $this->salt_hash = null;
            if (!empty($def['encrypted']) && $def['encrypted'] === true) {
                $this->salt_hash = $this->secretKey();
                $value = $this->encrypt($value, $this->salt_hash);
            }
            SystemSettingsModel::updateAll(
                ['current_value' => $value, 'salt' => $this->salt_hash],
                ['key' => $key, 'category' => $this->getCategory()]
            );
        }
        // Invalidate the cache for this tenant + category
        TagDependency::invalidate(Yii::$app->cache, $this->cachePrefix);
        return true;
    }

    public function getCategory(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function getItems(string $key): array
    {
        return $this->defineSettings()[$key]['items'] ?? [];
    }

    public function jsonSerialize(): array
    {
        return $this->cache;
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        return $this->jsonSerialize();
    }
}
