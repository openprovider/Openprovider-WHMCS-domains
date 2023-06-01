<?php

namespace WeDevelopCoffee\wPower\Validator;

use WeDevelopCoffee\wPower\Core\Core;

/**
 * Class Validator
 * @package WeDevelopCoffee\wPower\Validator
 */
class Validator
{
    /**
     * @var Core
     */
    private $core;

    /**
     * @var array $rules The rules.
     */
    protected $rules;

    /**
     * @var
     */
    protected $failedRules;

    /**
     * Validator constructor.
     * @param Core $core
     */
    public function __construct(Core $core)
    {
        $this->core = $core;
    }

    /**
     * Validate the rules.
     */
    public function validate()
    {
        foreach($this->rules as $field => $ruleSet)
        {
            $rules = explode("|", $ruleSet);

            foreach($rules as $rule)
            {
                $rule = strtolower($rule);
                switch($rule)
                {
                    case "required":
                        if(!isset($_REQUEST[$field]) || $_REQUEST[$field] == '' )
                            $this->failedRules[$field] = true;
                        continue;
                        break;
                }

                if(substr($rule, 0,10) == 'different_')
                {
                    $otherField = substr($rule, 10);

                    if(is_array($_REQUEST[$otherField]))
                    {
                        foreach($_REQUEST[$otherField] as $value)
                        {
                            $this->checkIfValueIsSimilarToField($otherField, $value, $field);
                        }
                    }
                    else
                        $this->checkIfValueIsSimilarToField($otherField, $value, $field);

                    continue;
                }
            }
        }
    }

    /**
     * Check if the validation has failed.
     *
     * @return boolean
     */
    public function failed()
    {
        $this->validate();

        if(empty($this->failedRules))
            return false;

        return true;
    }

    /**
     * @param mixed $rules
     * @return Validator
     */
    public function setRules($rules)
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFailedRules()
    {
        return $this->failedRules;
    }

    protected function checkIfValueIsSimilarToField($otherField, $otherValue, $field)
    {
        if($otherValue == $_REQUEST[$field])
        {
            $this->failedRules[$otherField] = true;
            $this->failedRules[$field] = true;
            return true;
        }

        return false;
    }
}