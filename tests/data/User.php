<?php
namespace yiiunit\extensions\graphql\data;

use GraphQL\Utils;

class User
{
    public $id;

    public $email;

    public $email2;

    public $firstName;

    public $lastName;

    public $hasPhoto;

    public function __construct(array $data)
    {
        Utils::assign($this, $data);
    }
}
