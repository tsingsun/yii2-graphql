<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/6/30
 * Time: 下午6:52
 */

namespace yii\graphql\types;


use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class PaginationType extends InputObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Pagination',
            'description' => '',
            'fields' => [
                'first' => [
                    'type' => Type::int(),
                    'description' => 'Returns the first n elements from the list.'
                ],
                'after' => [
                    'type' => Type::string(),
                    'description' => 'Returns the elements in the list that come after the specified global ID.'
                ],
                'last' => [
                    'type' => Type::int(),
                    'description' => 'Returns the last n elements from the list..'
                ],
                'before' => [
                    'type' => Type::string(),
                    'description' => 'Returns the elements in the list that come before the specified global ID.'
                ],
            ],
        ];

        parent::__construct($config);
    }

}