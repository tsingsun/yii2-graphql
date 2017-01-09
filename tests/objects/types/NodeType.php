<?php

namespace yiiunit\extensions\graphql\objects\types;

use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLInterfaceType;
use yii\graphql\GraphQL;

class NodeType extends GraphQLInterfaceType
{
    protected $attributes = [
        'name'=>'node'
    ];

    public function fields()
    {
        return [
            'id'=>Type::id()
        ];
    }

    public function resolveType($object, $types)
    {
        if ($object instanceof UserType) {
            return GraphQL::type('userType');
        } else if ($object instanceof ImageType) {
            return GraphQL::type('imageType');
        } else if ($object instanceof Story) {
            return GraphQL::type('storyType');
        }
    }
}
