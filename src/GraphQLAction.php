<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/5/18
 * Time: 下午3:10
 */

namespace yii\graphql;

use Yii;
use yii\base\Action;
use yii\web\Response;
use yii\base\InvalidParamException;

/**
 * GraphQLAction implements the access method of the graph server and returns the query results in the JSON format
 * configure in Controller actions method
 * ```php
 * function actions()
 * {
 *     return [
 *          'index'=>['class'=>'yii\graphql\GraphQLAction']
 *     ]
 * }
 * ```
 * @package yii\graphql
 */
class GraphQLAction extends Action
{
    const INTROSPECTIONQUERY = '__schema';
    /**
     * @var GraphQL
     */
    private $graphQL;
    private $schemaArray;
    private $query;
    private $variables;
    /**
     * @var array child graphql actions
     */
    private $authActions = [];
    /**
     * @var callable a PHP callable that will be called when running an action to determine
     * if the current user has the permission to execute the action. If not set, the access
     * check will not be performed. The signature of the callable should be as follows,
     *
     * ```php
     * function ($actionName) {
     *
     *     // If null, it means no specific model (e.g. IndexAction)
     * }
     * ```
     */
    public $checkAccess;
    /**
     * @var bool whether use Schema validation , and it is recommended only in the development environment
     */
    public $enableSchemaAssertValid = YII_ENV_DEV;

    public function init()
    {
        parent::init();

        $request = Yii::$app->getRequest();
        if ($request->isGet) {
            $this->query = $request->get('query');
            $this->variables = $request->get('variables');
        } else {
            $body = $request->getBodyParams();
            if (empty($body)) {
                //取原始文件当查询,这时只支持如其他方式下的query的节点的查询
                $this->query = $request->getRawBody();
            } else {
                $this->query = $body['query'] ?? $body;
                $this->variables = $body['variables'] ?? [];
            }
        }
        if (empty($this->query)) {
            throw new InvalidParamException('invalid query,query document not found');
        }
        if (is_string($this->variables)) {
            $this->variables = json_decode($this->variables, true);
        }

        /** @var GraphQLModuleTrait $module */
        $module = $this->controller->module;
        $this->graphQL = $module->getGraphQL();

        $this->schemaArray = $this->graphQL->parseRequestQuery($this->query);
    }

    /**
     * 返回本次查询的所有graphql action,如果本次查询为introspection时，则为查询的
     * @return array
     */
    public function getGraphQLActions()
    {
        if ($this->schemaArray === true) {
            return [self::INTROSPECTIONQUERY => 'true'];
        }
        $ret = array_merge($this->schemaArray[0], $this->schemaArray[1]);
        if (!$this->authActions) {
            //init
            $this->authActions = array_merge($this->schemaArray[0], $this->schemaArray[1]);
        }
        return $ret;
    }

    /**
     * remove action that no need check access
     * @param $key
     */
    public function removeGraphQlAction($key)
    {
        unset($this->authActions[$key]);
    }

    /**
     * @return array
     */
    public function run()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if ($this->authActions && $this->checkAccess) {
            foreach ($this->authActions as $childAction => $class) {
                $fn = $this->checkAccess;
                $fn($childAction);
            }
        }
        $schema = $this->graphQL->buildSchema($this->schemaArray === true ? null : $this->schemaArray);
        //TODO the graphql-php's valid too strict,the lazy load has can't pass when execute mutation(must has query node)
//        if ($this->enableSchemaAssertValid) {
//            $this->graphQL->assertValid($schema);
//        }
        $val = $this->graphQL->execute($schema, null, Yii::$app, $this->variables, null);
        $result = $this->graphQL->getResult($val);
        return $result;
    }
}