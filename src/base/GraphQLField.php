<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/15
 * Time: 上午9:53
 */

namespace yii\graphql\base;


use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use yii\graphql\GraphQL;

/**
 * Class GraphQLField
 *
 * @package yii\graphql\base
 */
class GraphQLField extends Fluent
{
    public function attributes()
    {
        return [];
    }

    public function type()
    {
        return null;
    }

    public function args()
    {
        return [];
    }

    /**
     * @param $value
     * @param $args
     * @param $context
     * @param ResolveInfo $info
     * @return null child class override
     */
//    protected abstract function resolve($value, $args, $context, ResolveInfo $info);

    protected function getResolver()
    {
        if (!method_exists($this, 'resolve')) {
            return null;
        }

        $resolver = array($this, 'resolve');
        return function () use ($resolver) {
            $args = func_get_args();
            return call_user_func_array($resolver, $args);
        };
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = $this->attributes();
        $args = $this->args();

        $attributes = array_merge($this->attributes, [
            'args' => $args
        ], $attributes);

        $type = $this->type();
        if (isset($type)) {
            if(!is_object($type)){
                $type = GraphQL::type($type);
            }
            $attributes['type'] = $type;
        }

        $resolver = $this->getResolver();
        if (isset($resolver)) {
            $attributes['resolve'] = $resolver;
        }

        return $attributes;
    }
}