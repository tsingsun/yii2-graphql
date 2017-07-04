<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/23
 * Time: 下午2:29
 */

namespace yiiunit\extensions\graphql\objects\query;


use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLQuery;
use yii\graphql\base\GraphQLType;
use yii\graphql\GraphQL;
use yii\graphql\types\SimpleExpressionType;
use yiiunit\extensions\graphql\data\DataSource;
use yiiunit\extensions\graphql\objects\types\NodeType;
use yiiunit\extensions\graphql\objects\types\UserType;

class NodeQuery extends GraphQLQuery
{
    public function type()
    {
        return GraphQL::type(NodeType::class);
    }

    public function args()
    {
        return [
            'id' => Type::nonNull(Type::id()),
            'type' => Type::string()
        ];
    }

    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        if ($args['type'] == 'user') {
            return DataSource::findUser($args['id']);
        } elseif ($args['type'] == 'story') {
            return DataSource::findStory($args['id']);
        }

    }


}