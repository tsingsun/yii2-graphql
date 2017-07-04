<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/29
 * Time: 下午2:49
 */

namespace yiiunit\extensions\graphql\objects\mutation;


use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLMutation;
use yii\graphql\GraphQL;
use yiiunit\extensions\graphql\data\DataSource;
use yiiunit\extensions\graphql\objects\models\UserModel;
use yiiunit\extensions\graphql\objects\types\UserType;

class UpdateUserPwdMutation extends GraphQLMutation
{
    protected $attributes = [
        'name' => 'updateUserPwd'
    ];

    public function type()
    {
        return GraphQL::type(UserType::class);
    }

    public function args()
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::nonNull(Type::string())
            ],
            'password' => [
                'name' => 'password',
                'type' => Type::nonNull(Type::string())
            ]
        ];
    }

    public function resolve($root, $args)
    {
        if ($args['id'] == 'qsli@google.com') {
            $args['id'] = 1;
        }
        $user = DataSource::findUser($args['id']);

        if(!$user)
        {
            return null;
        }

        $user->password = md5($args['password']);
        return $user;
    }

    public function rules()
    {
        return [
            ['id', 'email']
        ];
    }

}