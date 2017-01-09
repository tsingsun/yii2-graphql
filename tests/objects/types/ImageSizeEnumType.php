<?php
namespace yiiunit\extensions\graphql\objects\types;

use GraphQL\Type\Definition\EnumType;
use yiiunit\extensions\graphql\data\Image;

class ImageSizeEnumType extends EnumType
{
    public function __construct()
    {
        // Option #2: Define enum type using inheritance
        $config = [
            'name'=>'imageSizeEnum',
            // Note: 'name' option is not needed in this form - it will be inferred from className
            'values' => [
                'ICON' => [
                    'value'=>Image::SIZE_ICON
                ],
                'SMALL' => [
                    'value'=> Image::SIZE_SMALL,
                    ],
                'MEDIUM' => [
                    'value'=> Image::SIZE_MEDIUM,
                    ],
                'ORIGINAL' => [
                    'value'=> Image::SIZE_ORIGINAL
                    ],
            ]
        ];

        parent::__construct($config);
    }
}
