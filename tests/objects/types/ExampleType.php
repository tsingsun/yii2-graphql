<?php

namespace yiiunit\extensions\graphql\objects\types;

use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLType;
use yii\graphql\GraphQL;
use yii\graphql\types\EmailType;
use yii\graphql\types\Types;
use yiiunit\extensions\graphql\data\DataSource;
use yiiunit\extensions\graphql\data\User;

/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/18
 * Time: 上午9:46
 */
class ExampleType extends GraphQLType
{
    protected $attributes = [
        'name'=>'example',
        'description'=>'user is user'
    ];

    public function fields()
    {
        $result = [
            'id' => ['type'=>Type::id()],
            'email' => GraphQL::type(EmailType::class),
            'email2' => GraphQL::type(EmailType::class),
            'photo' => [
                'type' => GraphQL::type(ImageType::class),
                'description' => 'User photo URL',
                'args' => [
                    'size' => Type::nonNull(GraphQL::type(ImageSizeEnumType::class)),
                ]
            ],
            'firstName' => [
                'type' => Type::string(),
            ],
            'lastName' => [
                'type' => Type::string(),
            ],
            'lastStoryPosted' => GraphQL::type(StoryType::class),
            'fieldWithError' => [
                'type' => Type::string(),
                'resolve' => function() {
                    throw new \Exception("This is error field");
                }
            ]
        ];
        return $result;
    }

    public function resolvePhotoField(User $user,$args){
        return DataSource::getUserPhoto($user->id, $args['size']);
    }

    public function resolveIdField(User $user, $args)
    {
        return $user->id.'test';
    }

    public function resolveEmail2Field(User $user, $args)
    {
        return $user->email2.'test';
    }


}