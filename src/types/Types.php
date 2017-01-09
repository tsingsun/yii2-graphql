<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/18
 * Time: 上午10:00
 */

namespace yii\graphql\types;

/**
 * Class Types
 * it contains common type
 *
 * @package yii\graphql\type
 */
class Types
{
    private static $urlType;
    private static $emailType;

    public static function email()
    {
        return self::$emailType ?: (self::$emailType = new EmailType());
    }

    /**
     * @return UrlType
     */
    public static function url()
    {
        return self::$urlType ?: (self::$urlType = new UrlType());
    }
}