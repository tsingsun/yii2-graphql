<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/15
 * Time: 上午10:25
 */

namespace yii\graphql\base;

use GraphQL\Type\Definition\InterfaceType;

class GraphQLInterfaceType extends GraphQLType
{
    protected function getTypeResolver()
    {
        if (!method_exists($this, 'resolveType')) {
            return null;
        }

        $resolver = array($this, 'resolveType');
        return function () use ($resolver) {
            $args = func_get_args();
            return $resolver(...$args);
        };
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes($name = null, $except = null)
    {
        $attributes = parent::getAttributes();

        $resolver = $this->getTypeResolver();
        if (isset($resolver)) {
            $attributes['resolveType'] = $resolver;
        }

        return $attributes;
    }

    public function toType()
    {
        return new InterfaceType($this->toArray());
    }
}