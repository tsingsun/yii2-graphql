<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/15
 * Time: 上午10:00
 */

namespace yii\graphql\base;

use yii\base\Model;

/**
 * GraphQL attributes manager base on Yii Model
 * @package yii\graphql\base
 */
class GraphQLModel extends Model
{
    /**
     * All of the attributes set on the container.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return array_keys($this->attributes);
    }

    public function fields()
    {
        return $this->getAttributes();
    }

    /**
     * Converts the object into an array.
     *
     * @param array $fields the fields that the output array should contain. Fields not specified
     * in [[fields()]] will be ignored. If this parameter is empty, all fields as specified in [[fields()]] will be returned.
     * @param array $expand the additional fields that the output array should contain.
     * Fields not specified in [[extraFields()]] will be ignored. If this parameter is empty, no extra fields
     * will be returned.
     * @param boolean $recursive whether to recursively return array representation of embedded objects.
     * @return array the array representation of the object
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        return $this->getAttributes();
    }

    public function __get($name)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$name]) ? $attributes[$name] : null;
    }

    public function __isset($name)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$name]);
    }


    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function __unset($name)
    {
        unset($this->attributes[$name]);
    }


}