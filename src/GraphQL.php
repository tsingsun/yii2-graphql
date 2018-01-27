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
use GraphQL\Executor\Promise\Promise;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\Type;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryComplexity;
use Yii;
use yii\base\InvalidConfigException;
use yii\graphql\exceptions\TypeNotFound;
use yii\helpers\ArrayHelper;

/**
 * GraphQL facade class
 * In the use of each Module, graphql is independent and has no single example,
 * because it has a certain coupling with the Module instance.
 *
 * @package yii\graphql
 */
class GraphQL
{
    /**
     * @var array query map config
     */
    public $queries = [];
    /**
     * @var array mutation map config
     */
    public $mutations = [];
    /**
     * @var array type map config
     */
    public $types = [];

    public $errorFormatter;

    private $currentDocument;
    /**
     * @var TypeResolution
     */
    private $typeResolution;

    function __construct()
    {
    }

    /**
     * get TypeResolution
     * @return TypeResolution
     */
    public function getTypeResolution()
    {
        if (!$this->typeResolution) {
            $this->typeResolution = new TypeResolution();
        }
        return $this->typeResolution;
    }

    /**
     * Receive schema data and incorporate configuration information
     *
     * array format：
     * $schema = new [
     *   'query'=>[
     *      //the key is alias，mutation,types and so on
     *      'hello'=>HelloQuery::class
     *   ],
     *   'mutation'=>[],
     *   'types'=>[],
     * ];
     * @param null|array $schema 配置数组,该数组会导入对象自身的配置持久化下来
     */
    public function schema($schema = null)
    {
        if (is_array($schema)) {
            $schemaQuery = ArrayHelper::getValue($schema, 'query', []);
            $schemaMutation = ArrayHelper::getValue($schema, 'mutation', []);
            $schemaTypes = ArrayHelper::getValue($schema, 'types', []);
            $this->queries += $schemaQuery;
            $this->mutations += $schemaMutation;
            $this->types += $schemaTypes;
            $this->getTypeResolution()->setAlias($schemaTypes);
        }
    }

    /**
     * GraphQl Schema is built according to input. Especially,
     * due to the need of Module and Controller in the process of building ObjectType,
     * the execution position of the method is restricted to a certain extent.
     * @param Schema|array $schema schema data
     * @return Schema
     */
    public function buildSchema($schema = null)
    {
        if ($schema instanceof Schema) {
            return $schema;
        }
        if ($schema === null) {
            list($schemaQuery, $schemaMutation, $schemaTypes) = [$this->queries, $this->mutations, $this->types];
        } else {
            list($schemaQuery, $schemaMutation, $schemaTypes) = $schema;
        }
        $types = [];
        if (sizeof($schemaTypes)) {
            foreach ($schemaTypes as $name => $type) {
                $types[] = $this->getTypeResolution()->parseType($name, true);
            }
        }
        //graqhql的validator要求query必须有
        $query = $this->getTypeResolution()->objectType($schemaQuery, [
            'name' => 'Query'
        ]);

        $mutation = null;
        if (!empty($schemaMutation)) {
            $mutation = $this->getTypeResolution()->objectType($schemaMutation, [
                'name' => 'Mutation'
            ]);
        }

        $this->getTypeResolution()->initTypes([$query, $mutation], $schema == null);

        $result = new Schema([
            'query' => $query,
            'mutation' => $mutation,
            'types' => $types,
            'typeLoader' => function ($name) {
                return $this->getTypeResolution()->parseType($name, true);
            }
        ]);
        return $result;
    }


    /**
     * query access
     * @param $requestString
     * @param null $rootValue
     * @param null $contextValue
     * @param null $variableValues
     * @param string $operationName
     * @return array|Error\InvariantViolation
     */
    public function query($requestString, $rootValue = null, $contextValue = null, $variableValues = null, $operationName = '')
    {
        $sl = $this->parseRequestQuery($requestString);
        if ($sl === true) {
            $sl = [$this->queries, $this->mutations, $this->types];
        }
        $schema = $this->buildSchema($sl);

        $val = $this->execute($schema, $rootValue, $contextValue, $variableValues, $operationName);
        return $this->getResult($val);
    }

    /**
     * @param $executeResult
     * @return array|Promise
     */
    public function getResult($executeResult)
    {
        if ($executeResult instanceof ExecutionResult) {
            if ($this->errorFormatter) {
                $executeResult->setErrorFormatter($this->errorFormatter);
            }
            return $this->parseExecutionResult($executeResult);
        } elseif ($executeResult instanceof Promise) {
            return $executeResult->then(function (ExecutionResult $executionResult) {
                if ($this->errorFormatter) {
                    $executionResult->setErrorFormatter($this->errorFormatter);
                }
                return $this->parseExecutionResult($executionResult);
            });
        } else {
            throw new Error\InvariantViolation("Unexpected execution result");
        }
    }

    private function parseExecutionResult(ExecutionResult $executeResult)
    {
        if (empty($executeResult->errors) || empty($this->errorFormatter)) {
            return $executeResult->toArray();
        }
        $result = [];

        if (null !== $executeResult->data) {
            $result['data'] = $executeResult->data;
        }

        if (!empty($executeResult->errors)) {
            $result['errors'] = [];
            foreach ($executeResult->errors as $er) {
                $fn = $this->errorFormatter;
                $fr = $fn($er);
                if (isset($fr['message'])) {
                    $result['errors'][] = $fr;
                } else {
                    $result['errors'] += $fr;
                }
            }
//            $result['errors'] = array_map($executeResult->errorFormatter, $executeResult->errors);
        }

        if (!empty($executeResult->extensions)) {
            $result['extensions'] = (array)$executeResult->extensions;
        }

        return $result;
    }

    /**
     * Executing the query according to schema, this method needs to be executed after the schema is generated
     * @param $schema
     * @param $rootValue
     * @param $contextValue
     * @param $variableValues
     * @param $operationName
     * @return ExecutionResult|Promise
     */
    public function execute($schema, $rootValue, $contextValue, $variableValues, $operationName)
    {
        try {
            /** @var QueryComplexity $queryComplexity */
            $queryComplexity = DocumentValidator::getRule('QueryComplexity');
            $queryComplexity->setRawVariableValues($variableValues);

            $validationErrors = DocumentValidator::validate($schema, $this->currentDocument);

            if (!empty($validationErrors)) {
                return new ExecutionResult(null, $validationErrors);
            }
            return Executor::execute($schema, $this->currentDocument, $rootValue, $contextValue, $variableValues, $operationName);
        } catch (Error\Error $e) {
            return new ExecutionResult(null, [$e]);
        } finally {
            $this->currentDocument = null;
        }
    }

    /**
     * 将查询请求转换为可以转换为schema方法的数组
     * @param $requestString
     * @return array|bool 数组元素为0：query,1:mutation,2:types,当返回true时，表示为IntrospectionQuery
     */
    public function parseRequestQuery($requestString)
    {
        $source = new Source($requestString ?: '', 'GraphQL request');
        $this->currentDocument = Parser::parse($source);
        $queryTypes = [];
        $mutation = [];
        $types = [];
        $isAll = false;
        foreach ($this->currentDocument->definitions as $definition) {
            if ($definition instanceof OperationDefinitionNode) {
                $selections = $definition->selectionSet->selections;
                foreach ($selections as $selection) {
                    $node = $selection->name;
                    if ($node instanceof NameNode) {
                        if ($definition->operation == 'query') {
                            if ($definition->name && $definition->name->value == 'IntrospectionQuery') {
                                $isAll = true;
                                break 2;
                            }
                            if (isset($this->queries[$node->value])) {
                                $queryTypes[$node->value] = $this->queries[$node->value];
                            }
                            if (isset($this->types[$node->value])) {
                                $types[$node->value] = $this->types[$node->value];
                            }
                        } elseif ($definition->operation == 'mutation') {
                            if (isset($this->mutations[$node->value])) {
                                $mutation[$node->value] = $this->mutations[$node->value];
                            }
                        }
                    }
                }
            }
        }
        return $isAll ?: [$queryTypes, $mutation, $types];
    }

    /**
     * Type manager access
     * @param string|Type $name
     * @param bool $byAlias if use alias
     * @return mixed
     */
    public static function type($name, $byAlias = false)
    {
        /** @var GraphQLModuleTrait $module */
        $module = Yii::$app->controller ? Yii::$app->controller->module : Yii::$app->getModule('graphql');
        $gql = $module->getGraphQL();

        return $gql->getTypeResolution()->parseType($name, $byAlias);
    }

    /**
     * @param $class
     * @param null $name
     */
    public function addType($class, $name = null)
    {
        $name = $this->getTypeName($class, $name);
        $this->types[$name] = $class;
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

        $type = is_object($class) ? $class : Yii::createObject($class);
        return $type->name;
    }

    /**
     * set error formatter
     * @param Callable $errorFormatter
     */
    public function setErrorFormatter(Callable $errorFormatter)
    {
        $this->errorFormatter = $errorFormatter;
    }

    /**
     * validate the schema.
     *
     * when initial the schema,the types parameter must not passed.
     *
     * @param Schema $schema
     */
    public function assertValid($schema)
    {
        //the type come from the TypeResolution.
        foreach ($this->types as $name => $type) {
            $schema->getType($name);
        }
        $schema->assertValid();
    }
}