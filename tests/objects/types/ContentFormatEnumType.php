<?php
namespace yiiunit\extensions\graphql\objects\types;

use GraphQL\Examples\Blog\Type\BaseType;
use GraphQL\Type\Definition\EnumType;

class ContentFormatEnumType extends EnumType
{
    const FORMAT_TEXT = 'TEXT';
    const FORMAT_HTML = 'HTML';

    public function __construct()
    {
        $config = [
            'name' => 'ContentFormatEnum',
            'values' => [self::FORMAT_TEXT, self::FORMAT_HTML]
        ];
        parent::__construct($config);
    }
}
