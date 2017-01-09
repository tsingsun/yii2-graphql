<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/23
 * Time: 下午3:26
 */

namespace yiiunit\extensions\graphql\objects\query;


use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLQuery;

class HelloQuery extends GraphQLQuery
{
    protected $attributes = [
        'name'=>'hello',
    ];

    public function type()
    {
        return Type::string();
    }


    protected function resolve($value, $args, $context, ResolveInfo $info)
    {
        return 'Your graphql-php endpoint is ready! Use GraphiQL to browse API';
    }


}