<?php

namespace yiiunit\extensions\graphql\objects\types;

use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLType;
use yii\graphql\GraphQL;
use yiiunit\extensions\graphql\data\Comment;
use yiiunit\extensions\graphql\data\DataSource;

class CommentType extends GraphQLType
{
    protected $attributes = [
        'name'=>'comment',
        'description'=>'user make a view for story',
    ];

    public function fields()
    {
        return [
            'id'=>Type::id(),
            'author'=>GraphQL::type(UserType::class),
            'parent'=>GraphQL::type(CommentType::class),
            'isAnonymous'=>Type::boolean(),
            'replies' => [
                'type' => Type::listOf(GraphQL::type(CommentType::class)),
                'args' => [
                    'after' => Type::int(),
                    'limit' => [
                        'type' => Type::int(),
                        'defaultValue' => 5
                    ]
                ]
            ],
            'totalReplyCount' => Type::int(),
        ];
    }

    public function resolveAuthorField(Comment $comment)
    {
        if ($comment->isAnonymous) {
            return null;
        }
        return DataSource::findUser($comment->authorId);
    }

    public function resolveParentField(Comment $comment)
    {
        if ($comment->parentId) {
            return DataSource::findComment($comment->parentId);
        }
        return null;
    }

    public function resolveRepliesField(Comment $comment, $args)
    {
        $args += ['after' => null];
        return DataSource::findReplies($comment->id, $args['limit'], $args['after']);
    }

    public function resolveTotalReplyCountField(Comment $comment)
    {
        return DataSource::countReplies($comment->id);
    }
}
