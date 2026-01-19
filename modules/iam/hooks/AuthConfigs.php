<?php

namespace iam\hooks;

use Yii;
use yii\caching\Cache;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\ArrayHelper;

class AuthConfigs extends \yii\base\BaseObject
{
    const CACHE_TAG = 'yiitron.auth';

    /**
     * @var ManagerInterface .
     */
    public $authManager = 'authManager';

    /**
     * @var Connection Database connection.
     */
    public $db = 'db';
    /**
     * @var Cache Cache component.
     */
    public $cache = 'cache';

    /**
     * @var integer Cache duration. Default to a hour.
     */
    public $cacheDuration = 3600;

    /**
     * @var boolean If false then AccessControl will check without Rule.
     */
    public $strict = true;

    /**
     * @var array
     */
    public $options;


    /**
     * @var self Instance of self
     */
    private static $_instance;
    private static $_classes = [
        'db' => 'yii\db\Connection',
        'cache' => 'yii\caching\Cache',
        'authManager' => \helpers\auth\ManagerInterface::class,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        foreach (self::$_classes as $key => $class) {
            try {
                $this->{$key} = empty($this->{$key}) ? null : Instance::ensure($this->{$key}, $class);
            } catch (\Exception $exc) {
                $this->{$key} = null;
                Yii::error($exc->getMessage());
            }
        }
    }

    /**
     * Create instance of self
     * @return static
     */
    public static function instance()
    {
        if (self::$_instance === null) {
            $type = ArrayHelper::getValue(Yii::$app->params, 'configs', []);
            if (is_array($type) && !isset($type['class'])) {
                $type['class'] = static::class;
            }

            return self::$_instance = Yii::createObject($type);
        }

        return self::$_instance;
    }

    public static function __callStatic($name, $arguments)
    {
        $instance = static::instance();
        if ($instance->hasProperty($name)) {
            return $instance->$name;
        } else {
            if (count($arguments)) {
                $instance->options[$name] = reset($arguments);
            } else {
                return array_key_exists($name, $instance->options) ? $instance->options[$name] : null;
            }
        }
    }

    /**
     * @return Connection
     */
    public static function db()
    {
        return static::instance()->db;
    }

    /**
     * @return Cache
     */
    public static function cache()
    {
        return static::instance()->cache;
    }

    /**
     * @return ManagerInterface
     */
    public static function authManager()
    {
        return static::instance()->authManager;
    }
    /**
     * @return integer
     */
    public static function cacheDuration()
    {
        return static::instance()->cacheDuration;
    }

    /**
     * @return boolean
     */
    public static function strict()
    {
        return static::instance()->strict;
    }
}
