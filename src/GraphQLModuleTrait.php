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
 * graph特性类，一般是辅助Module类，初始化GraphQl
 * @author QingShan Li
 */
trait GraphQLModuleTrait
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
     * @var GraphQL the Graph handle
     */
    private $graphQL;

    /**
     * 启用webonyx graphql config的验证
     */
    public function enableValidation()
    {
        Config::enableValidation();
    }

    /**
     * 构建Schema数据，一般不需要提前构建，系统可以按需构建schema
     */
    public function buildSchema()
    {
        $this->getGraphQL()->buildSchema();
    }

    /**
     * get graphql handler
     * @return GraphQL
     */
    public function getGraphQL(){
        if($this->graphQL == null){
            $this->graphQL = new GraphQL();
            $this->graphQL->schema($this->schemas);
        }
        return $this->graphQL;
    }
}