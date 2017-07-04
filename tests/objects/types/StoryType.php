<?php

namespace yiiunit\extensions\graphql\objects\types;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLType;
use yii\graphql\GraphQL;
use yii\web\Application;
use yiiunit\extensions\graphql\data\DataSource;
use yiiunit\extensions\graphql\data\Story;

/**
 * Class StoryType
 * @package GraphQL\Examples\Social\Type
 */
class StoryType extends GraphQLType
{
    const EDIT = 'EDIT';
    const DELETE = 'DELETE';
    const LIKE = 'LIKE';
    const UNLIKE = 'UNLIKE';
    const REPLY = 'REPLY';

    protected $attributes = [
        'name'=>'story',
        'description'=>'it is a story'
    ];

    public function interfaces()
    {
        return [GraphQL::type(NodeType::className())];
    }

    public function fields()
    {
        return [
            'id' => ['type'=>Type::id()],
            'author' => GraphQL::type(UserType::class),
//            'mentions' => Type::listOf(Types::mention()),
            'totalCommentCount' => ['type'=>Type::int()],
            'comments' => [
                'type' => Type::listOf(GraphQL::type(CommentType::class)),
                'args' => [
                    'after' => [
                        'type' => Type::id(),
                        'description' => 'Load all comments listed after given comment ID'
                    ],
                    'limit' => [
                        'type' => Type::int(),
                        'defaultValue' => 5
                    ]
                ]
            ],
            'likes' => [
                'type' => Type::listOf(GraphQL::type(UserType::class)),
                'args' => [
                    'limit' => [
                        'type' => Type::int(),
                        'description' => 'Limit the number of recent likes returned',
                        'defaultValue' => 5
                    ]
                ]
            ],
            'likedBy' => [
                'type' => Type::listOf(GraphQL::type(UserType::class)),
            ],
            'affordances' => ['type'=>Type::listOf(new EnumType([
                'name' => 'StoryAffordancesEnum',
                'values' => [
                    self::EDIT,
                    self::DELETE,
                    self::LIKE,
                    self::UNLIKE,
                    self::REPLY
                ]
            ]))],
            'hasViewerLiked' => ['type'=>Type::boolean()],

            'body'=>HtmlField::class,
        ];
    }

    public function resolveAuthorField(Story $story)
    {
        return DataSource::findUser($story->authorId);
    }

    public function resolveAffordancesField(Story $story, $args, Application $context)
    {
        $viewer = $context->user->getIdentity();
        $isViewer = $viewer === DataSource::findUser($story->authorId);
        $isLiked = DataSource::isLikedBy($story->id, $viewer->getId());

        if ($isViewer) {
            $affordances[] = self::EDIT;
            $affordances[] = self::DELETE;
        }
        if ($isLiked) {
            $affordances[] = self::UNLIKE;
        } else {
            $affordances[] = self::LIKE;
        }
        return $affordances;
    }

    public function resolveHasViewerLikedField(Story $story, $args, Application $context)
    {
        return DataSource::isLikedBy($story->id, $context->getUser()->getId());
    }

    public function resolveTotalCommentCountField(Story $story)
    {
        return DataSource::countComments($story->id);
    }

    public function resolveCommentsField(Story $story, $args)
    {
        $args += ['after' => null];
        return DataSource::findComments($story->id, $args['limit'], $args['after']);
    }
}
