<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/14
 * Time: 上午11:46
 */

namespace yii\graphql;

use Yii;
use yii\graphql\GraphQL;
use yii\base\Controller;

class GraphQLController extends Controller
{
    /**
     * @var GraphQL
     */
    private $graphQL;

    public function actionIndex(){
        $request = Yii::$app->getRequest();
        $query = $request->get('query');
        $params = $request->get('variables');
        if(!$query){
            //TODO not get http method have to parse content
            $query = $request->getBodyParams();
        }

        if (is_string($params)){
            $params = json_decode($params,true);
        }
        /** @var GraphQLModuleTrait $module */
        $module = $this->module;
        $this->graphQL = $module->getGraphQL();

        $result = $this->graphQL->query($query,null,Yii::$app,$params);

        return $result;

    }
}