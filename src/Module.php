<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/14
 * Time: 上午11:42
 */

namespace yii\graphql;

use Yii;
use GraphQL\Type\Definition\Config;
use yii\base\BootstrapInterface;
use yii\caching\Cache;

/**
 * The Yii Graphql Module provides the facebook graphql server
 * @author QingShan Li
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    /**
     * // The schemas for query and/or mutation. It expects an array to provide
     * both the 'query' fields and the 'mutation' fields. You can also
     * provide directly an object GraphQL\Schema
     *
     * Example:
     *
     * 'schemas' => [
     *      'query' => [
     *          'user' => 'App\GraphQL\Query\UsersQuery'
     *      ],
     *      'mutation' => [
     *
     *      ],
     *      'types'=>[
     *          'user'=>'app\modules\graph\type\UserType'
     *      ],
     * ]
     *
     * @var array
     */
    public $schemas = [];
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'yii\graphql\controllers';

    /**
     * @var Cache | string the cache object or the ID of the cache application component that
     * is used to cache the schema metadata.
     */
    public $cache = 'cache';

    /**
     * @var GraphQL the Graph handle
     */
    private $graphQL;

    public function init()
    {
        parent::init();
        if(YII_DEBUG){
            Config::enableValidation();
        }
    }

    /**
     * get graphql handler
     * @return GraphQL
     */
    public function getGraphQL(){
        if($this->graphQL == null){
            $this->graphQL = new GraphQL();
        }
        return $this->graphQL;
    }

    /**
     * @return string|Cache the Cache use in graphql
     */
    public function getCache(){
        $cache = is_string($this->cache) ? Yii::$app->get($this->cache, false) : $this->cache;
        return $cache;
    }

    public function bootstrap($app)
    {
        $this->bootSchemas();
//        $this->bootTypes();
    }

    public function bootTypes(){
        foreach($this->schemas['types'] as $name=>$type){
            $this->getGraphQL()->addType($type,$name);
        }
    }

    public function bootSchemas(){
        $graphql = $this->getGraphQL();
        $graphql->queries = $this->schemas['query'];
        $graphql->types = $this->schemas['types'];
        $graphql->mutations = $this->schemas['mutation'];
//        foreach($this->schemas as $name=>$schema){
//            $this->getGraphQL()->addSchema($name,$schema);
//        }
    }

}