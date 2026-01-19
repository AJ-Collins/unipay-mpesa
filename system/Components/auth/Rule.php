<?php

namespace helpers\auth;

use yii\helpers\Inflector;

/**
 * Rule represents a business constraint that may be associated with a role, permission or assignment.
 * 
 * This class extends Yii's RBAC Rule and provides additional functionality for managing
 * business rules in the authorization system. Rules are used to implement dynamic permission
 * checks based on runtime conditions.
 * 
 * @package helpers\auth
 * @author Ananda Douglas <douglasdaggs@gmail.com>
 */
class Rule extends \yii\rbac\Rule
{
    use \helpers\traits\Keygen;

    /**
     * @var mixed Additional data associated with the rule.
     *           Can be used to store configuration or metadata needed for rule execution.
     */
    public $data;

    /**
     * @var string Hash identifier for the rule.
     *            Generated using the Keygen trait for unique identification.
     */
    public $hash;

    /**
     * @var string The module this rule belongs to.
     *            Used for organizing rules by functional area or application module.
     */
    public $module;

    /**
     * @var string Description of the rule's purpose.
     *            Provides human-readable explanation of what this rule checks.
     * @default '-- No description provided --'
     */
    public $description = '-- No description provided --';

    /**
     * @var string Human-readable title for the rule.
     *            Auto-generated from the class name using camelCase to words conversion.
     */
    public $rule_title;

    /**
     * Constructor.
     * 
     * Initializes the rule with the given configuration. If no name is provided,
     * it automatically generates one from the class name by removing the 'Rule' suffix.
     * The rule_title is also auto-generated from the class name in a human-readable format.
     * 
     * @param array $config Name-value pairs that will be used to initialize the object properties.
     */
    public function __construct($config = [])
    {
        $className = (new \ReflectionClass($this))->getShortName();
        if (empty($this->name)) {
            $this->name = preg_replace('/Rule$/', '', $className);
        }
        $this->rule_title = Inflector::camel2words($className);
        parent::__construct($config);
    }

    /**
     * Executes the business logic of this rule.
     * 
     * This method should be overridden in child classes to implement specific
     * authorization logic. The default implementation calls the parent method
     * and should return a boolean value indicating whether access is granted.
     *
     * @param string|int $user The user ID being checked for access.
     * @param \yii\rbac\Item $item The role or permission that this rule is associated with.
     * @param array $params Additional runtime parameters passed to ManagerInterface::checkAccess()
     *                     that can be used in the rule logic.
     * @return bool Whether the rule permits the associated role or permission.
     *             Returns true if access should be granted, false otherwise.
     */
    public function execute($user, $item, $params)
    {
        return parent::execute($user, $item, $params);
    }
}

