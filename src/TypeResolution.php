<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/7/4
 * Time: 下午2:57
 */

namespace yii\graphql;

use GraphQL\Type\Definition\AbstractType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Utils;
use Yii;
use yii\base\InvalidConfigException;
use yii\graphql\base\GraphQLType;
use yii\graphql\base\GraphQLField;
use yii\graphql\exceptions\TypeNotFound;
use yii\base\NotSupportedException;
use GraphQL\Type\Introspection;

class TypeResolution
{
    /**
     * @var array<className,string> alias for className to map the type
     */
    private $alias = [];
    /**
     * @var Type[]
     */
    private $typeMap = [];

    /**
     * @var array<string, ObjectType[]>
     */
    private $implementations = [];

    /**
     * EagerResolution constructor.
     */
    public function __construct()
    {

    }

    /**
     * set type config
     * @param $config
     */
    public function setAlias($config)
    {

        $this->alias = $config;
    }

    /**
     * @param Type[] $graphTypes
     * @param bool $needIntrospection if need IntrospectionQuery set true
     */
    public function initTypes($graphTypes, $needIntrospection = false)
    {
        $typeMap = [];
        if ($needIntrospection) {
            $graphTypes[] = Introspection::_schema();
        }
        foreach ($graphTypes as $type) {
            $typeMap = Utils\TypeInfo::extractTypes($type, $typeMap);
        }
        $this->typeMap = $typeMap + Type::getInternalTypes();

        // Keep track of all possible types for abstract types
        foreach ($this->typeMap as $typeName => $type) {
            if ($type instanceof ObjectType) {
                foreach ($type->getInterfaces() as $iface) {
                    $this->implementations[$iface->name][] = $type;
                }
            }
        }
    }

    /**
     * transform single type
     * @param Type $type
     */
    protected function transformType($type)
    {
        if ($type instanceof ObjectType) {
            foreach ($type->getInterfaces() as $iface) {
                $this->implementations[$iface->name][] = $type;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function resolveType($name)
    {
        return isset($this->typeMap[$name]) ? $this->typeMap[$name] : $this->parseType($name, true);
    }

    /**
     * @inheritdoc
     */
    public function resolvePossibleTypes(AbstractType $abstractType)
    {
        if (!isset($this->typeMap[$abstractType->name])) {
            return [];
        }

        if ($abstractType instanceof UnionType) {
            return $abstractType->getTypes();
        }

        /** @var InterfaceType $abstractType */
        Utils::invariant($abstractType instanceof InterfaceType);
        return isset($this->implementations[$abstractType->name]) ? $this->implementations[$abstractType->name] : [];
    }

    /**
     * @return Type[]
     */
    public function getTypeMap()
    {
        return $this->typeMap;
    }

    /**
     * Returns serializable schema representation suitable for GraphQL\Type\LazyResolution
     *
     * @return array
     */
    public function getDescriptor()
    {
        $typeMap = [];
        $possibleTypesMap = [];
        foreach ($this->getTypeMap() as $type) {
            if ($type instanceof UnionType) {
                foreach ($type->getTypes() as $innerType) {
                    $possibleTypesMap[$type->name][$innerType->name] = 1;
                }
            } else if ($type instanceof InterfaceType) {
                foreach ($this->implementations[$type->name] as $obj) {
                    $possibleTypesMap[$type->name][$obj->name] = 1;
                }
            }
            $typeMap[$type->name] = 1;
        }
        return [
            'version' => '1.0',
            'typeMap' => $typeMap,
            'possibleTypeMap' => $possibleTypesMap
        ];
    }

    /**
     * convert type declare to ObjectType instance
     * @param $type
     * @param array $opts
     * @return ObjectType|null
     */
    public function objectType($type, $opts = [])
    {
        // If it's already an ObjectType, just update properties and return it.
        // If it's an array, assume it's an array of fields and build ObjectType
        // from it. Otherwise, build it from a string or an instance.
        $objectType = null;
        if ($type instanceof ObjectType) {
            $objectType = $type;
            foreach ($opts as $key => $value) {
                if (property_exists($objectType, $key)) {
                    $objectType->{$key} = $value;
                }
                if (isset($objectType->config[$key])) {
                    $objectType->config[$key] = $value;
                }
            }
        } elseif (is_array($type)) {
            $objectType = $this->buildObjectTypeFromFields($type, $opts);
        } else {
            $objectType = $this->buildObjectTypeFromClass($type, $opts);
        }

        return $objectType;
    }

    /**
     * build ObjectType from classname  config
     * @param Object|array $type 能够转换为ObjectType的类实例或者类配置
     * @param array $opts
     * @return object
     * @throws InvalidConfigException
     */
    protected function buildObjectTypeFromClass($type, $opts = [])
    {
        if (!is_object($type)) {
            $type = Yii::createObject($type);
        }

        foreach ($opts as $key => $value) {
            $type->{$key} = $value;
        }

        return $type->toType();
    }

    /**
     * Configuring GraphQL ObjectType through the graphql declaration configuration
     * @param array $fields use standard graphql declare.
     * @param array $opts
     * @return ObjectType
     * @throws InvalidConfigException
     */
    protected function buildObjectTypeFromFields($fields, $opts = [])
    {
        $typeFields = [];
        foreach ($fields as $name => $field) {
            if (is_string($field)) {
                $field = Yii::createObject($field);
                $name = is_numeric($name) ? $field->name : $name;
                $field['name'] = $name;
                $field = $field->toArray();
            } else {
                $name = is_numeric($name) ? $field['name'] : $name;
                $field['name'] = $name;
            }
            $typeFields[$name] = $field;
        }

        return new ObjectType(array_merge([
            'fields' => $typeFields
        ], $opts));
    }

    /**
     * get type by name,this method is use in Type definition class for TypeSystem
     * @param $name
     * @param bool $byAlias if use alias;
     * @return Type|null
     * @throws TypeNotFound | NotSupportedException
     */
    public function parseType($name, $byAlias = false)
    {
        $class = $name;
        if (is_object($class)) {
            $name = get_class($class);
        }

        if ($byAlias && isset($this->alias[$name])) {
            $class = $this->alias[$name];
        } elseif (!$byAlias && isset($this->alias[$name])) {
            $name = $this->alias[$name];
        }

        if (isset($this->typeMap[$name])) {
            return $this->typeMap[$name];
        }

        //class is string or not found;
        if (is_string($class)) {
            if (strpos($class, '\\') !== false && !class_exists($class)) {
                throw new TypeNotFound('Type ' . $name . ' not found.');
            }

        } elseif (!is_object($class)) {
            throw new TypeNotFound('Type ' . $name . ' not found.');
        }
        $type = $this->buildType($class);
        $this->alias[$type->name] = $class;
        $this->alias[$class] = $type->name;
        $this->typeMap[$type->name] = $type;
        $this->transformType($type);
        return $type;
    }

    /**
     * @param string $type type name
     * @param array $opts return Type's attribute set
     * @return ObjectType|Type|GraphQLField
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    protected function buildType($type)
    {
        if (!is_object($type)) {
            $type = Yii::createObject($type);
        }
        if ($type instanceof Type) {
            return $type;
        } elseif ($type instanceof GraphQLType) {
            //transfer ObjectType
            return $type->toType();
        } elseif ($type instanceof GraphQLField) {
            //field is not need transfer to ObjectType,it just need config array
            return $type;
        }

        throw new NotSupportedException("Type:{$type} is not support translate to Graph Type");
    }
}