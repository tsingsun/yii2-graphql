<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/23
 * Time: 下午2:39
 */

namespace yiiunit\extensions\graphql\objects\query;


use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLQuery;
use yii\graphql\GraphQL;
use yiiunit\extensions\graphql\data\DataSource;
use yiiunit\extensions\graphql\objects\types\StoryType;

class StoryListQuery extends GraphQLQuery
{
    protected $attributes = [
        'name'=>'stories',
        'description'=>'Returns subset of stories posted for this blog',
    ];

    public function type()
    {
        return Type::listOf(GraphQL::type(StoryType::class));
    }

    public function args()
    {
        return [
            'after'=>[
                'type'=>Type::id(),
                'description'=>'Fetch stories listed after the story with this ID'
            ],
            'limit' => [
                'type' => Type::int(),
                'description' => 'Number of stories to be returned',
                'defaultValue' => 10
            ]
        ];
    }

    protected function resolve($value, $args, $context, ResolveInfo $info)
    {
        $args += ['after' => null];
        return DataSource::findStories($args['limit'], $args['after']);
    }


}