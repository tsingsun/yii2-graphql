<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/7/3
 * Time: 下午4:23
 */

namespace yiiunit\extensions\graphql\objects\types;


use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLType;
use yii\graphql\GraphQL;

class ResultItemConnectionType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ResultItemConnection'
    ];

    public function fields()
    {
        return [
            'nodes' => Type::listOf(GraphQL::type(ResultItemType::className())),
        ];
    }
}