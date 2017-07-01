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
use yiiunit\extensions\graphql\objects\types\UserType;

class UserQuery extends GraphQLQuery
{
    public function type()
    {
        return GraphQL::type(UserType::class);
    }

    public function args()
    {
        return [
            'id'=>[
                'type'=>Type::nonNull(Type::id())
            ],
        ];
    }

    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        return DataSource::findUser($args['id']);
    }


}