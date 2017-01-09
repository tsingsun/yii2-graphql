<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/14
 * Time: 上午11:46
 */

namespace yii\graphql\controllers;

use Yii;
use yii\graphql\GraphQL;
use yii\web\Controller;

class DefaultController extends Controller
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

        $this->graphQL = $this->module->getGraphQL();

        $result = $this->graphQL->query($query,null,Yii::$app,$params);

        return $result;

    }
}