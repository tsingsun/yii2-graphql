<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/6/29
 * Time: 上午9:38
 */

namespace yii\graphql\types;

use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLType;

/**
 * Class SimpleExpressionType
 * @package yii\graphql\types
 */
class SimpleExpressionType extends GraphQLType
{
    protected $inputObject = true;

    private static $operatorMap = [
        'eq' => '=',
        'gt' => '>',
        'lt' => '<',
        'gte' => '>=',
        'lte' => '<=',
    ];

    protected $attributes = [
        'name' => 'FieldCondition',
        'description' => 'simple query expression,backend parse it to prepare to query data source',
    ];

    public function fields()
    {
        return [
            'gt' => [
                'type' => Type::int(),
                'description' => 'great than',
            ],
            'gte' => [
                'type' => Type::int(),
                'description' => 'great than or equals',
            ],
            'lt' => [
                'type' => Type::int(),
                'description' => 'less than',
            ],
            'lte' => [
                'type' => Type::int(),
                'description' => 'less than or equals',
            ],
            'eq' => [
                'type' => Type::string(),
                'description' => 'equals',
            ],
            'in' => [
                'type' => Type::listOf(Type::int()),
                'description' => 'value in list',
            ],
        ];
    }

    public static function toQueryCondition($source)
    {
        $ret = [];
        foreach ($source as $key => $value) {
            if (is_scalar($value)) {
                $ret[$key] = $value;
            } elseif (is_array($value)) {
                $opExp = key($value);
                $op = self::$operatorMap[$opExp]??$opExp;
                $ret[] = [$op, $key, $value[$opExp]];
            }
        }
        return $ret;
    }
}