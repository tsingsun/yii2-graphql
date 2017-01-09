<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/14
 * Time: 下午3:23
 */

namespace yii\graphql;

use GraphQL\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Executor;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryComplexity;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\graphql\base\ActiveRecordType;
use yii\graphql\base\GraphQLField;
use yii\graphql\base\GraphQLType;
use yii\graphql\exception\SchemaNotFound;
use yii\graphql\exception\TypeNotFound;
use yii\helpers\ArrayHelper;

/**
 * the Graphql facade
 * @package yii\graphql
 */
class GraphQL
{
    /**
     * @var Module
     */
    protected static $module;
    public $queries = [];
    public $mutations = [];
//    protected $schemas = [];
    public $types = [];
    protected $typesInstances = [];

    /**
     * @return null|Module
     */
    public static function getModule(){
        if(!self::$module){
            self::$module = Yii::$app->getModule('graphql');
        }
        return self::$module;
    }
    /**
     * Generate GraphQL Schema.
     * @param null $schema
     * @return array|Schema|null|string in common condition,it will be null,add it is easy to unit test
     * @throws SchemaNotFound
     * @throws TypeNotFound
     */
    public function schema($schema = null)
    {
        $beginTime = microtime(true);
        if ($schema instanceof Schema) {
            return $schema;
        }

        if(is_array($schema)){
            $schemaQuery = ArrayHelper::getValue($schema, 'query', []);
            $schemaMutation = ArrayHelper::getValue($schema, 'mutation', []);
            $schemaTypes = ArrayHelper::getValue($schema, 'types', []);
            $this->queries += $schemaQuery;
            $this->mutations += $schemaMutation;
            $this->types += $schemaTypes;
        }

        $result = $this->buildSchema($this->queries,$this->mutations,$this->types);

        $duringTime = microtime(true)-$beginTime;
        Yii::trace("parse schema during : {$duringTime} second");
        return $result;
    }

    private function buildSchema($schemaQuery,$schemaMutation,$schemaTypes){
        $types = [];
        if (sizeof($schemaTypes)) {
            foreach ($schemaTypes as $name => $type) {
                $types[] = $this->getType($name);
            }
        }
        $query = $this->objectType($schemaQuery, [
            'name' => 'Query'
        ]);
        $mutation = $this->objectType($schemaMutation, [
            'name' => 'Mutation'
        ]);
        $result = new Schema([
            'query' => $query,
            'mutation' => $mutation,
            'types' => $types
        ]);
        return $result;
    }

    /**
     * generate ObjectType instance
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
     * @param $type
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
        foreach ($opts as $key => $value) {
            $type->{$key} = $value;
        }

        return $type->toType();
    }

    /**
     * build ObjectType from array config
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
                $name = is_numeric($name) ? $field->name:$name;
                $field->name = $name;
                $field = $field->toArray();
            } else {
                $name = is_numeric($name) ? $field['name']:$name;
                $field['name'] = $name;
            }
            $typeFields[$name] = $field;
        }

        return new ObjectType(array_merge([
            'fields' => $typeFields
        ], $opts));
    }


    /**
     * @param $query
     * @param null $rootValue
     * @param null $contextValue
     * @param null $variableValues
     * @param string $operationName
     * @return array
     */
    public function query($query,$rootValue = null,$contextValue = null,$variableValues = null,$operationName = ''){
        $result = $this->queryAndReturnResult($query,$rootValue,$contextValue,$variableValues,$operationName);

        $return = [];
        if (null !== $result->data) {
            $return['data'] = $result->data;
        }

        if (!empty($this->errors)) {
            $return['errors'] = array_map([$this, 'formatError'], $result->errors);
        }

        if (!empty($this->extensions)) {
            $return['extensions'] = (array) $result->extensions;
        }

        return $return;
    }
    /**
     * @param $query
     * @param null $rootValue
     * @param null $contextValue
     * @param null $variableValues
     * @param string $operationName
     * @return ExecutionResult
     */
    public function queryAndReturnResult($query,$rootValue = null,$contextValue = null,$variableValues = null,$operationName = ''){
        try{
            $source = new Source($query ?: '', 'GraphQL request');
            $documentNode = Parser::parse($source);
            //TODO mutation parse is not add
            $queryTypes = [];
            $mutation = [];
            $types = [];
            foreach($documentNode->definitions as $definition){
                if($definition instanceof OperationDefinitionNode){
                    $selections = $definition->selectionSet->selections;
                    foreach($selections as $selection){
                        $node = $selection->name;
                        if($node instanceof NameNode){
                            if($definition->operation == 'query'){
                                if(isset($this->queries[$node->value])){
                                    $queryTypes[$node->value] = $this->queries[$node->value];
                                }
                                if(isset($this->types[$node->value])){
                                    $types[$node->value] = $this->types[$node->value];
                                }
                            }elseif($definition->operation == 'mutation'){
                                if(isset($this->mutations[$node->value])){
                                    $mutation[$node->value] = $this->mutations[$node->value];
                                }
                            }
                        }
                    }
                }
            }
            $schema = $this->buildSchema($queryTypes,$mutation,$types);

            /** @var QueryComplexity $queryComplexity */
            $queryComplexity = DocumentValidator::getRule('QueryComplexity');
            $queryComplexity->setRawVariableValues($variableValues);

            $validationErrors = DocumentValidator::validate($schema, $documentNode);

            if (!empty($validationErrors)) {
                return new ExecutionResult(null, $validationErrors);
            } else {
                return Executor::execute($schema, $documentNode, $rootValue, $contextValue, $variableValues, $operationName);
            }
        }catch(Error\Error $e){

        }
    }

    /**
     * static method for getType
     * @param $name
     * @return mixed
     */
    public static function type($name){
        $gql = self::getModule()->getGraphQL();
        return $gql->getType($name);
    }

    /**
     * get type by name,this method is use in Type definition class for TypeSystem
     * @param $name
     * @return ObjectType|null
     * @throws TypeNotFound
     */
    public function getType($name)
    {
        $class = $name;
        if (isset($this->types[$name])) {
            $class = $this->types[$name];

            if(is_object($class)){
                return $class;
            }
        }

        //class is string or not found;

        if(strpos($class,'\\')!==false && !class_exists($class)){
            throw new TypeNotFound('Type '.$name.' not found.');
        }
        $type = $this->buildType($class);
        $this->types[$name] = $type;

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

        if($type instanceof GraphQLType){
            //transfer ObjectType
            return $type->toType();
        }elseif($type instanceof GraphQLField){
            //field is not need transfer to ObjectType,it just need config array
            return $type;
        }elseif($type instanceof ActiveRecord){
            //transfer ObjectType
            $type = new ActiveRecordType($type);
            return $type->toType();
        }elseif($type instanceof Type){
            return $type;
        }

        throw new NotSupportedException("Type:{$type} is not support translate to Graph Type");
    }

    /**
     * @param $class
     * @param null $name
     */
    public function addType($class, $name = null)
    {
        $name = $this->getTypeName($class, $name);
        $this->types[$name] = $class;

//        event(new TypeAdded($class, $name));
    }

    /**
     *
     * @param $class
     * @param null $name
     * @return null
     * @throws InvalidConfigException
     */
    protected function getTypeName($class, $name = null)
    {
        if ($name) {
            return $name;
        }

        $type = is_object($class) ? $class:Yii::createObject($class);
        return $type->name;
    }

    public static function formatError(Error\Error $e)
    {
        $error = [
            'message' => $e->getMessage()
        ];

        $locations = $e->getLocations();
        if (!empty($locations)) {
            $error['locations'] = array_map(function ($loc) {
                return $loc->toArray();
            }, $locations);
        }

        $previous = $e->getPrevious();
        if ($previous && $previous instanceof ValidationError) {
            $error['validation'] = $previous->getValidatorMessages();
        }

        return $error;
    }
}