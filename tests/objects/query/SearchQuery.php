<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/23
 * Time: 下午2:29
 */

namespace yiiunit\extensions\graphql\objects\query;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLQuery;
use yii\graphql\GraphQL;
use yii\web\Application;
use yiiunit\extensions\graphql\data\DataSource;
use yiiunit\extensions\graphql\objects\types\ResultItemConnectionType;
use yiiunit\extensions\graphql\objects\types\ResultItemType;
use yiiunit\extensions\graphql\objects\types\UserType;

class SearchQuery extends GraphQLQuery
{
    protected $attributes = [
        'name' => 'search',
        'description' => 'search user or story',
    ];

    public function args()
    {
        return [
            'limit' => Type::int(),
            'after' => Type::int(),
            'query' => Type::string(),
            'type' => new EnumType(['name' => 't', 'values' => ['user' => ['value' => 'user'], 'story' => ['value' => 'story']]]),
        ];
    }

    public function type()
    {
        return GraphQL::type(ResultItemConnectionType::className());
    }

    protected function resolve($value, $args, Application $context, ResolveInfo $info)
    {
        $result = [];
        if ($args['type'] == 'user') {
            $result = [DataSource::findUser(1)];
        } elseif ($args['type'] == 'story') {
            $result = DataSource::findStories($args['limit'], $args['after']);
        }
        return ['nodes' => $result];
    }
}