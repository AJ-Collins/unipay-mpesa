<?php

namespace helpers\cron;

use Yii;
use helpers\Migration;
use helpers\ActiveRecord;
use yii\base\DynamicModel;
use yii\helpers\FileHelper;

class SchedulerModel extends ActiveRecord
{
    private static bool $_tableEnsured = false;

    public function fields()
    {
        $fields = [
            'task_id',
            'task_title',
            'system_service' => function ($model) {
                return $this->pkHash($model->system_service);
            },
            'schedule_type',
            'schedule_value',
            'last_run_at' => function ($model) {
                return Yii::$app->settings->DateTime($model->last_run_at);
            },
            'next_run_at' => function ($model) {
                return Yii::$app->settings->DateTime($model->next_run_at);
            },
            'status' => function ($model) {
                return $model->recordStatus;
            },
            'service_payload' => function ($model) {
                $data = $model->service_payload;

                if ($model->system_service && class_exists($model->system_service)) {
                    $instance = new $model->system_service();

                    if (property_exists($instance, 'dataObject')) {
                        $property = (array)($instance->dataObject ?? []);
                        $name = $property['name'] ?? null;

                        if ($name && isset($model->service_payload[$name])) {
                            $currentData = $model->service_payload;
                            unset($currentData[$name]);
                            $data = array_merge($currentData, $model->service_payload[$name]);
                        }
                    }
                }

                return $data;
            },
        ];

        // Conditionally add is_recurring ONLY if schedule_type is 'interval'
        if ($this->schedule_type === 'interval') {
            $fields['is_recurring'] = 'is_recurring';
        }

        return $fields;
    }
    public static function tableName()
    {
        static::ensureTableExistsOnce();

        return '{{%scheduled_tasks}}';
    }

    public function rules()
    {
        return [
            [['task_title'], 'required'],
            [['task_title'], 'unique', 'message' => 'Task with a similar title already exists.'],
            [['system_service'], 'required', 'message' => 'Service cannot be blank. Please select a valid service from the list.'],
            [['next_run_at'], 'integer'],
            [['is_recurring'], 'boolean'],
            [['system_service', 'task_title'], 'string', 'max' => 255],
            [['schedule_type'], 'in', 'range' => ['interval', 'cron']],
            [['schedule_value'], 'required'],
            [['service_payload'], 'safe'],
            [['system_service'], 'validateSystemService'],
            [['system_service'], 'validateJobClass'],
            // Validate ONLY when schedule_type is 'cron'
            [['schedule_value'], 'validateCronExpression', 'when' => function ($model) {
                return $model->schedule_type === 'cron';
            }, 'skipOnEmpty' => false],
        ];
    }
    public function validateSystemService($attribute, $params)
    {
        $class = $this->$attribute;
        if (!is_string($class) || trim($class) === '') {
            $this->addError($attribute, 'Corrupt or invalid service');
            return;
        }
        if (!class_exists($class)) {
            $this->addError($attribute, "Service does not exist");
            return;
        }
        $reflection = new \ReflectionClass($class);
        if ($reflection->isAbstract() || $reflection->isInterface()) {
            $this->addError($attribute, "Service cannot be abstract or an interface");
        }
    }
    public function validateJobClass($attribute, $params)
    {
        $class = $this->$attribute;
        if (!is_subclass_of($class, \yii\queue\JobInterface::class)) {
            $this->addError($attribute, "Service does not conform to required structure");
        }
    }
    public function validateCronExpression($attribute, $params)
    {
        if ($this->schedule_type !== 'cron') {
            return; // Skip if not cron
        }

        $value = trim($this->$attribute);

        if ($value === '') {
            $this->addError($attribute, 'Cron expression cannot be empty.');
            return;
        }

        try {
            // This constructor accepts:
            // - Standard 5-field expressions
            // - Macros (@hourly, @daily, etc.)
            // - L, W, #n syntax
            $cron = new \Cron\CronExpression($value);

            // Force calculation of next run date to catch subtle invalid cases
            // e.g., "32 * * * *" (invalid minute) will throw here
            $cron->getNextRunDate();

            // Optional: Prevent overly frequent schedules (e.g., every second)
            // Uncomment if you want to limit to minimum 1 minute

            $next = $cron->getNextRunDate();
            $prev = $cron->getPreviousRunDate();
            if (($next->getTimestamp() - $prev->getTimestamp()) < 60) {
                $this->addError($attribute, 'A task cannot be scheduled to run more than once per minute.');
            }
        } catch (\InvalidArgumentException $e) {
            $this->addError($attribute, 'Invalid cron expression: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->addError($attribute, 'Invalid cron expression. Common examples: '
                . '"0 9 * * *" (daily at 9 AM), '
                . '"*/30 * * * *" (every 30 minutes), '
                . '"0 0 L * *" (midnight on last day of month), '
                . '"@daily" (midnight daily).');
        }
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
            (new class extends Migration {
                protected function beginCommand($description)
                {
                    return true;
                }

                protected function endCommand($time)
                {
                    return true;
                }

                public function safeUp()
                {
                    $this->createTable('{{%scheduled_tasks}}', [
                        'task_id' => $this->primaryKey(),
                        'task_title' => $this->string()->notNull()->unique(),
                        'system_service' => $this->string()->notNull(),
                        'schedule_type' => $this->string(16)->notNull(),
                        'schedule_value' => $this->string()->null(),
                        'service_payload' => $this->json(),
                        'is_recurring' => $this->boolean()->defaultValue(false),
                        'last_run_at' => $this->integer()->null(),
                        'next_run_at' => $this->integer()->notNull(),
                        'locked_at' => $this->integer()->null(),
                        'is_deleted' => $this->boolean()->defaultValue(false),
                        'status' => $this->integer()->notNull()->defaultValue(10),
                        'created_at' => $this->integer()->notNull(),
                        'updated_at' => $this->integer()->notNull(),
                    ]);
                    $this->createIndex('idx_next_run_status', '{{%scheduled_tasks}}', ['next_run_at', 'status']);
                }

                public function safeDown()
                {
                    $this->dropTable('{{%scheduled_tasks}}');
                }
            })->safeUp();
        }
    }

    /**
     * Returns raw table name without Yii's {{%}} placeholder
     */
    protected static function tableNameRaw(): string
    {
        return str_replace(['{{%', '}}'], ['', ''], '{{%scheduled_tasks}}');
    }

    /**
     * Validates payload data based on the system_service schema.
     * Returns true if valid, false otherwise. Populates $errors with any validation errors.
     */
    public function validatePayload(array $payloadData, array &$errors = []): bool
    {
        $errors = [];

        if (!$this->system_service || !class_exists($this->system_service)) {
            return true;
        }

        $instance = new $this->system_service();
        $schema = (array)($instance->payload ?? []);

        if (empty($schema)) {
            return true;
        }

        // Build Yii rules from schema
        $rules = [];
        foreach ($schema as $def) {
            $key = $def['key'];
            if (!empty($def['validations']) && is_array($def['validations'])) {
                foreach ($def['validations'] as $validation) {
                    if (is_array($validation)) {
                        $rule = $validation;
                        $rule[0] = [$key];
                        $rules[] = $rule;
                    } else {
                        $rules[] = [[$key], $validation];
                    }
                }
            }
        }

        // Use DynamicModel to validate
        $payloadModel = DynamicModel::validateData($payloadData, $rules);
        if ($payloadModel->hasErrors()) {
            foreach ($payloadModel->errors as $attr => $errMsgs) {
                $errors[$attr] = $errMsgs[0]; // Only keep fields that failed
            }
            return false;
        }

        // No errors -> return true without populating with nulls
        return true;
    }


    /**
     * Gets the payload schema fields for form rendering (same as before).
     */
    public function getPayloadFields($withValidations = false): array
    {
        if (!$this->system_service || !class_exists($this->system_service)) {
            return [];
        }

        $instance = new $this->system_service();
        $schema = (array)($instance->payload ?? []);

        $fields = [];
        foreach ($schema as $key => $def) {
            $options =  $def['predefinedOptions'] ?? [];
            $fields[$key] = [
                'name'   => $def['key'],
                'label'  => $def['label'] ?? ucfirst($def['key']),
                'type'   => $def['type'] ?? 'textInput',
                //'predefinedOptions' => $def['predefinedOptions'] ?? [],
            ];
            if ($withValidations) {
                $fields[$key] = array_merge($fields[$key], [
                    'validations'  => $def['validations'] ?? [],
                ]);
            }
            if (!empty($options)) {
                $fields[$key] = array_merge($fields[$key], [
                    'predefinedOptions'  => $options,
                ]);
            }
        }

        return $fields;
    }

    public function getAvailableServices($id = null): array
    {
        $modulesPath = Yii::getAlias('@app/modules');
        $jobClasses = [];

        if (is_dir($modulesPath)) {
            foreach (scandir($modulesPath) as $moduleName) {
                if ($moduleName === '.' || $moduleName === '..') {
                    continue;
                }

                $jobsDir = $modulesPath . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'jobs';
                if (is_dir($jobsDir)) {
                    $files = FileHelper::findFiles($jobsDir, [
                        'only' => ['*Job.php'],
                        'recursive' => false,
                    ]);

                    foreach ($files as $key => $file) {
                        $className = pathinfo($file, PATHINFO_FILENAME);
                        $shortName = preg_replace('/Job$/', '', $className);
                        $fullClass = "\\{$moduleName}\\jobs\\{$className}";
                        $job_id = $this->pkHash($fullClass);
                        if (class_exists($fullClass)) {
                            $ref = new \ReflectionClass($fullClass);
                            if ($ref->hasProperty('payload') && $ref->getProperty('payload')->hasDefaultValue()) {
                                $jobClasses[$job_id]['key'] = $job_id;
                                $jobClasses[$job_id]['fullClass'] = $fullClass;
                                $jobClasses[$job_id]['label'] = \yii\helpers\Inflector::camel2words($shortName) . ' Service';
                                $jobClasses[$job_id]['fields'] = (new $fullClass())->payload ?? [];
                            }
                        }
                    }
                }
            }
        }
        return $id ?  $jobClasses[$id] : $jobClasses;
    }
    public function getShortName()
    {
        return preg_replace('/Job$/', '', (new \ReflectionClass($this->system_service))->getShortName());
    }
}
