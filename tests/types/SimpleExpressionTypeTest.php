<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/6/30
 * Time: 上午9:55
 */

namespace yiiunit\extensions\graphql\objects\types;

use yii\graphql\types\SimpleExpressionType;
use yiiunit\extensions\graphql\TestCase;

class SimpleExpressionTypeTest extends TestCase
{
    public function testToQueryCondition()
    {
        $express = [
            'id' => 1,
            'name' => [
                'eq' => 'abc'
            ],
            'count' => [
                'lt' => 1
            ],
            'age' => [
                'gt' => 20
            ],
        ];
        $expect = [
            'id' => 1,
            ['=', 'name', 'abc'],
            ['<', 'count', 1],
            ['>', 'age', 20],
        ];

        $val = SimpleExpressionType::toQueryCondition($express);
        $this->assertEquals($expect, $val);
    }
}
