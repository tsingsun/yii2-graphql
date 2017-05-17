<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/15
 * Time: 上午10:26
 */

namespace yii\graphql\base;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use yii\graphql\GraphQL;

class GraphQLType extends Fluent
{
    protected $inputObject = false;

    public function fields()
    {
        return [];
    }

    public function interfaces()
    {
        return [];
    }

    /**
     * The resolver for a specific field.
     *
     * @param $name
     * @param $field
     * @return \Closure|null
     */
    protected function getFieldResolver($name, $field)
    {
        $resolveMethod = 'resolve' . ucfirst($name) . 'Field';
        if (is_array($field) && isset($field['resolve'])) {
            return $field['resolve'];
        } elseif (method_exists($this, $resolveMethod)) {
            $resolver = array($this, $resolveMethod);
            return function () use ($resolver) {
                $args = func_get_args();
                return call_user_func_array($resolver, $args);
            };
        }

        return null;
    }

    /**
     * the the field parsed,field could defined like:
     *  //GraphQlType Node
     *      'fieldByType'=> GraphQl::types([typeName = 'className'|'ConfigName']);
     *      'field1ByTypeClassName'=> UserType::class
     *  //GraphQLField Node
     *      'field2'=> HtmlField::class
     *
     * @return array
     */
    public function getFields()
    {
        $fields = $this->fields();
        $allFields = [];
        foreach ($fields as $name => $field) {
            //the field is a GraphQlType or GraphQLField
            if (is_string($field)) {
                $type = GraphQL::type($field);
                if ($type instanceof GraphQLField) {
                    $field = $type->toArray();
                    $field['name'] = $name;
                } else {
                    $field = [
                        'name' => $name,
                        'type' => $type,
                    ];
                }
            } elseif ($field instanceof Type) {
                $field = [
                    'name' => $name,
                    'type' => $field
                ];

            }
            $resolver = $this->getFieldResolver($name, $field);
            if ($resolver) {
                $field['resolve'] = $resolver;
            }
            $allFields[$name] = $field;
        }

        return $allFields;
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = array_merge($this->attributes, [
            'fields' => function () {
                return $this->getFields();
            }
        ]);

        $interfaces = $this->interfaces();
        if (sizeof($interfaces)) {
            $attributes['interfaces'] = $interfaces;
        }

        return $attributes;
    }

    /**
     * Convert this class to its ObjectType.
     *
     * @return ObjectType |InputObjectType
     */
    public function toType()
    {
        if ($this->inputObject) {
            return new InputObjectType($this->toArray());
        }
        return new ObjectType($this->toArray());
    }
}