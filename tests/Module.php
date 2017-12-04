<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/5/16
 * Time: 下午3:22
 */

namespace yiiunit\extensions\graphql;


use GraphQL\Type\Definition\Config;
use yii\graphql\GraphQLModuleTrait;

class Module extends \yii\base\Module
{
    use GraphQLModuleTrait;

    public function init()
    {
    }
}