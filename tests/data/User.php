<?php
namespace yiiunit\extensions\graphql\data;

use GraphQL\Utils\Utils;
use yii\web\IdentityInterface;

class User implements IdentityInterface
{
    public $id;

    public $email;

    public $email2;

    public $firstName;

    public $lastName;

    public $hasPhoto;

    public $password;

    public function __construct(array $data)
    {
        Utils::assign($this, $data);
    }

    public static function findIdentity($id)
    {
        return DataSource::findUser($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return DataSource::findUser(1);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        // TODO: Implement getAuthKey() method.
    }

    public function validateAuthKey($authKey)
    {
        // TODO: Implement validateAuthKey() method.
    }


}
