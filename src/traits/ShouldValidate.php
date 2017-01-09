<?php

namespace yii\graphql\traits;

use yii\helpers\ArrayHelper;
use yii\validators\Validator;
use yii\graphql\error\ValidationError;

trait ShouldValidate
{
    protected function rules()
    {
        return [];
    }
    
    public function getRules()
    {
        $arguments = func_get_args();
        $args = $this->args();

        if ($this instanceof RelayMutation) {
            $args = $this->inputFields();
        }

        $rules = call_user_func_array([$this, 'rules'], $arguments);
        $argsRules = [];
        foreach ($args as $name => $arg) {
            if (isset($arg['rules'])) {
                if (is_callable($arg['rules'])) {
                    $argsRules[$name] = call_user_func_array($arg['rules'], $arguments);
                } else {
                    $argsRules[$name] = $arg['rules'];
                }
            }
        }
        
        return array_merge($rules, $argsRules);
    }
    
    protected function getValidator($args, $rules)
    {
        return Validator::createValidator($args, $rules);
    }
    
    protected function getResolver()
    {
        $resolver = parent::getResolver();
        if (!$resolver) {
            return null;
        }
        
        return function () use ($resolver) {
            $arguments = func_get_args();
            
            $rules = call_user_func_array([$this, 'getRules'], $arguments);
            if (sizeof($rules)) {
                $args = ArrayHelper::getValue($arguments, 1, []);
                $validator = $this->getValidator($args, $rules);
                if ($validator->fails()) {
                    throw with(new ValidationError('validation'))->setValidator($validator);
                }
            }
            
            return call_user_func_array($resolver, $arguments);
        };
    }
}
