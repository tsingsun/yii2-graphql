<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/23
 * Time: 上午10:46
 */

namespace yiiunit\extensions\graphql\objects\types;


use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\base\GraphQLField;
use yii\graphql\base\GraphQLType;
use yii\graphql\GraphQL;

class HtmlField extends GraphQLField
{
    protected $attributes = [
        'description'=>'a html tag',
    ];

    public function type()
    {
        return Type::string();
    }

    public function args()
    {
        return [
            'format' => [
                'type' => GraphQL::type(ContentFormatEnumType::class),
                'defaultValue' => ContentFormatEnumType::FORMAT_HTML,
            ],
            'maxLength' => Type::int(),
        ];
    }

    public function resolve($root, $args,$context,ResolveInfo $info)
    {
//        $fields = $info->getFieldSelection($depth = 3);
        $html = $root->{$info->fieldName};
        $text = strip_tags($html);

        if (!empty($args['maxLength'])) {
            $safeText = mb_substr($text, 0, $args['maxLength']);
        } else {
            $safeText = $text;
        }

        switch ($args['format']) {
            case ContentFormatEnumType::FORMAT_HTML:
                if ($safeText !== $text) {
                    // Text was truncated, so just show what's safe:
                    return nl2br($safeText);
                } else {
                    return $html;
                }

            case ContentFormatEnumType::FORMAT_TEXT:
            default:
                return $safeText;
        }
    }

}