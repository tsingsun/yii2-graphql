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
use yii\graphql\GraphQL;
use yii\web\Application;
use yiiunit\extensions\graphql\objects\types\UserType;

class ViewerQuery extends GraphQLQuery
{
    protected $attributes = [
        'description'=>'Represents currently logged-in user (for the sake of example - simply returns user with id == 1)',
    ];

    public function type()
    {
        return GraphQL::type(UserType::class);
    }

    protected function resolve($value, $args, Application $context, ResolveInfo $info)
    {
        return $context->user->getIdentity();
    }
}