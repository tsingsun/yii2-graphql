<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/6/30
 * Time: 下午5:57
 */

namespace yii\graphql\types;


use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PageInfoType extends ObjectType
{
    function __construct()
    {
        $config = [
            'name' => 'PageInfo',
            'description' => 'Information about pagination in a connection',
            'fields' => [
                'endCursor' => [
                    'type' => Type::string(),
                    'description' => 'When paginating forwards, the cursor to continue.'
                ],
                'hasNextPage' => [
                    'type' => Type::boolean(),
                    'description' => 'When paginating forwards, are there more items?',
                ],
                'hasPreviousPage' => [
                    'type' => Type::boolean(),
                    'description' => 'When paginating backwards, are there more items?',
                ],
                'startCursor' => [
                    'type' => Type::string(),
                    'description' => 'When paginating backwards, the cursor to continue.',
                ],
            ],
        ];
        parent::__construct($config);
    }
}