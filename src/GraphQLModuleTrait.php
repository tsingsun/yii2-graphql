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

/**
 * graph特性类，一般是辅助Module类，初始化GraphQl
 * @author QingShan Li
 */
trait GraphQLModuleTrait
{
    /**
     * The schemas for query and/or mutation. It expects an array to provide
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
    public $schema = [];

    /**
     * @var GraphQL the Graph handle
     */
    private $graphQL;

    /**
     * @var callable if don't set error formatter,it will use php-graphql default
     * @see \GraphQL\Executor\ExecutionResult
     */
    public $errorFormatter;

    /**
     * webonyx graphql config validation for debug
     */
    public function enableValidation()
    {
        Config::enableValidation();
    }

    /**
     * get graphql handler
     * @return GraphQL
     */
    public function getGraphQL(){
        if($this->graphQL == null){
            $this->graphQL = new GraphQL();
            $this->graphQL->schema($this->schema);
            if($this->errorFormatter){
                $this->graphQL->setErrorFormatter($this->errorFormatter);
            }
        }
        return $this->graphQL;
    }
}