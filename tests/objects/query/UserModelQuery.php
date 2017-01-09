<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/28
 * Time: 下午3:54
 */

namespace yiiunit\extensions\graphql\objects\query;


use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLQuery;
use yii\graphql\GraphQL;
use yiiunit\extensions\graphql\objects\models\UserModel;

class UserModelQuery extends GraphQLQuery
{
    public function type()
    {
        return Type::listOf(GraphQL::type(UserModel::class));
    }

    public function args()
    {
        return [
            'id'=>[
                'type'=>Type::id(),
                'description'=>'用户的ID'
            ],
            'pageIndex'=>[
                'type'=>Type::int(),
                'description'=>''
            ],
            'pageSize'=> [
                'type'=> Type::int(),
                'description'=>'',
            ],
        ];
    }

    public function resolve($root,$args,$context,ResolveInfo $info){
        $id = $args['id'];
        $id = $id?:'1001';
        return UserModel::findAll(['id'=>$id]);
    }

}