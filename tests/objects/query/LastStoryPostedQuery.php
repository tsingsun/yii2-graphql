<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/23
 * Time: 下午2:59
 */

namespace yiiunit\extensions\graphql\objects\query;


use GraphQL\Type\Definition\ResolveInfo;
use yii\graphql\base\GraphQLQuery;
use yii\graphql\GraphQL;
use yiiunit\extensions\graphql\data\DataSource;
use yiiunit\extensions\graphql\objects\types\StoryType;

class LastStoryPostedQuery extends GraphQLQuery
{
    protected $attributes = [
        'description'=>'Returns last story posted for this blog',
    ];

    public function type()
    {
        return GraphQL::type(StoryType::class);
    }

    protected function resolve($value, $args, $context, ResolveInfo $info)
    {
        return DataSource::findLatestStory();
    }


}