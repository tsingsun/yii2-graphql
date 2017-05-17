<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/14
 * Time: 上午11:46
 */

namespace yii\graphql;

use Yii;
use yii\base\InvalidParamException;
use yii\base\Controller;
use yii\web\Response;

class GraphQLController extends Controller
{
    /**
     * @var GraphQL
     */
    private $graphQL;

    public function actionIndex(){
        $request = Yii::$app->getRequest();
        if ($request->isGet) {
            $query = $request->get('query');
            $variables = $request->get('variables');
        } else {
            $body = $request->getBodyParams();
            $query = $body['query']??$body;
            $variables = $body['variables']??[];
        }
        if (empty($query)) {
            throw new InvalidParamException('invalid query,query document not found');
        }

        if (is_string($variables)){
            $variables = json_decode($variables,true);
        }
        /** @var GraphQLModuleTrait $module */
        $module = $this->module;
        $this->graphQL = $module->getGraphQL();
        if(YII_DEBUG){
            //调度状态下将执行构建查询
            $module->enableValidation();
//            $this->graphQL->buildSchema();

        }
        $result = $this->graphQL->query($query,null,Yii::$app,$variables);
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $result;

    }
}