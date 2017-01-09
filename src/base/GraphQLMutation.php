<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/15
 * Time: 上午10:31
 */

namespace yii\graphql\base;


use yii\graphql\traits\ShouldValidate;

class GraphQLMutation extends GraphQLField
{
    use ShouldValidate;
}