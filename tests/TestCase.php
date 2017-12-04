<?php

namespace yiiunit\extensions\graphql;

use GraphQL\Type\Schema;
use Yii;
use yii\di\Container;
use yii\helpers\ArrayHelper;
use yiiunit\extensions\graphql\data\DataSource;
use yiiunit\extensions\graphql\objects\mutation\UpdateUserPwdMutation;
use yiiunit\extensions\graphql\objects\query\HelloQuery;
use yiiunit\extensions\graphql\objects\query\LastStoryPostedQuery;
use yiiunit\extensions\graphql\objects\query\NodeQuery;
use yiiunit\extensions\graphql\objects\query\SearchQuery;
use yiiunit\extensions\graphql\objects\query\StoryListQuery;
use yiiunit\extensions\graphql\objects\query\UserModelQuery;
use yiiunit\extensions\graphql\objects\query\UserQuery;
use yiiunit\extensions\graphql\objects\query\ViewerQuery;
use yiiunit\extensions\graphql\objects\types\ExampleType;
use yiiunit\extensions\graphql\objects\types\StoryType;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $queries;
    protected $data;
    protected $config;

    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->destroyApplication();
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
        ], $config));
    }

    protected function mockWebApplication($config = [], $appClass = '\yii\web\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
                'graphQLCache' => [
                    'class' => 'yii\caching\FileCache',
                    'cachePath' => '@runtime/graphql',
                    'directoryLevel' => 0,
                ],
                'db'=>[
                    'class' => 'yii\db\Connection',
                    'dsn' => 'mysql:host=localhost;dbname=test',
                    'username' => 'root',
                    'password' => '',
                    'charset' => 'utf8',
                ],
                'user' => [
                    'class' => 'yii\web\User',
                    'identityClass' => 'yiiunit\extensions\graphql\data\User'
                ],

            ],
            'modules' => [
                'graphql' => [
                    'class' => Module::class,
                    'schema' => [
                        'query' => [
                            'hello' => HelloQuery::class,
                            'user' => UserQuery::class,
                            'viewer' => ViewerQuery::class,
                            'stories' => StoryListQuery::class,
                            'lastStoryPosted' => LastStoryPostedQuery::class,
                            'search' => SearchQuery::className(),
                            'node' => NodeQuery::className(),
                        ],
                        'mutation' => [
                            'updateUserPwd' => UpdateUserPwdMutation::class
                        ],
                        'types' => [
                            'example' => ExampleType::class,
                            'story' => StoryType::class,
//                            'comment' => CommentType::class,
//                            'image' => ImageType::class,
//                            'imageSizeEnum' => ImageSizeEnumType::class,
//                            'ContentFormatEnum' => ContentFormatEnumType::class,
                        ],
                    ],
                ]
            ],
            'bootstrap' => [
                'graphql'
            ],
        ], $config));
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
        Yii::$container = new Container();
    }

    /**
     * Invokes object method, even if it is private or protected.
     * @param object $object object.
     * @param string $method method name.
     * @param array $args method arguments
     * @return mixed method result
     */
    protected function invoke($object, $method, array $args = [])
    {
        $classReflection = new \ReflectionClass(get_class($object));
        $methodReflection = $classReflection->getMethod($method);
        $methodReflection->setAccessible(true);
        $result = $methodReflection->invokeArgs($object, $args);
        $methodReflection->setAccessible(false);
        return $result;
    }

    protected function setUp()
    {
        parent::setUp();
        $this->queries = include(__DIR__ . '/objects/queries.php');
        DataSource::init();
    }


    protected function assertGraphQLSchema($schema)
    {
        $this->assertInstanceOf('GraphQL\Type\Schema', $schema);
    }

    /**
     * @param Schema $schema
     * @param $key
     */
    protected function assertGraphQLSchemaHasQuery($schema, $key)
    {
        //Query
        $query = $schema->getQueryType();
        $queryFields = $query->getFields();
        $this->assertArrayHasKey($key, $queryFields);

        $queryField = $queryFields[$key];
        $queryListType = $queryField->getType();
        $queryType = $queryListType->getWrappedType();
        $this->assertInstanceOf('GraphQL\Type\Definition\FieldDefinition', $queryField);
        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $queryListType);
        $this->assertInstanceOf('GraphQL\Type\Definition\ObjectType', $queryType);
    }

    /**
     * @param Schema $schema
     * @param $key
     */
    protected function assertGraphQLSchemaHasMutation($schema, $key)
    {
        //Mutation
        $mutation = $schema->getMutationType();
        $mutationFields = $mutation->getFields();
        $this->assertArrayHasKey($key, $mutationFields);

        $mutationField = $mutationFields[$key];
        $mutationType = $mutationField->getType();
        $this->assertInstanceOf('GraphQL\Type\Definition\FieldDefinition', $mutationField);
        $this->assertInstanceOf('GraphQL\Type\Definition\ObjectType', $mutationType);
    }
}
