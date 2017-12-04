yii-graphql
==========
Using Facebook [GraphQL](http://facebook.github.io/graphql/) PHP server implementation. Extend [graphql-php](https://github.com/webonyx/graphql-php) to apply to YII2.
 
[![Latest Stable Version](https://poser.pugx.org/tsingsun/yii2-graphql/v/stable.svg)](https://packagist.org/packages/tsingsun/yii2-graphql)
[![Build Status](https://travis-ci.org/tsingsun/yii2-graphql.png?branch=master)](https://travis-ci.org/tsingsun/yii2-graphql)
[![Total Downloads](https://poser.pugx.org/tsingsun/yii2-graphql/downloads.svg)](https://packagist.org/packages/tsingsun/yii2-graphql)

--------

[Chinese document](/docs/README-zh.md)

-------

Features

* Configuration simplification includes simplifying the definition of standard graphql protocols.
* Based on the full name defined by the type, implemented on-demand loading and lazy loading, and no need to load all type definitions into the system at the initial stage
* Mutation input validation support
* Provide controller integration and authorization support

### Install ###

use composer 
```
composer require tsingsun/yii2-graphql
```

### Type ###
The type system is the core of GraphQL, which is embodied in GraphQLType. By deconstructing the graphql protocol and using the graph-php library to achieve fine-grained control of all elements, it is convenient to extend the class according to its own needs


The main elements of GraphQLType,** Notice that the element does not correspond to attributes or methods (the same below) **

element  | type | description
----- | ----- | -----
name | string | **Required** Each type needs to be named, and if it is the only one that is more secure, but not mandatory, the property needs to be defined in attribute
fields | array | **Required** The included field content is represented by the fields () method.
resolveField | callback | **function($value, $args, $context, GraphQL\Type\Definition\ResolveInfo $info)** For the interpretation of a field, such as the fields definition of the user property, the corresponding interpretation method is resolveUserField (), and $value is specified as a type instance defined by type

### Query ###

GraphQLQuery,GraphQLMutation inherited GraphQLField,The element structure is consistent, and you want to do it for some reusable Field, you can inherit it.
Each query of Graphql needs to correspond to a GraphQLQuery object

The main elements of GraphQLField

 element | type  | description
----- | ----- | -----
type | ObjectType | For the corresponding query type, the single type is specified by GraphQL:: type, and the list by Type:: listOf (GraphQL:: type)
args | array | Query the parameters that need to be used, each of which is defined by Field
resolve | callback | **function($value, $args, $context, GraphQL\Type\Definition\ResolveInfo $info)**,$value is the root data, $args is the query parameter, the $context context is the yii\web\Application object, and the $info resolves the object for the query. The root object is handled in this method

### Mutation ###

Very similar to GraphQLQuery, refer to the instructions

### Simplified  ###

Simplifies the declarations of Field, and the fields can be directly used by type

```php
standard
    'id'=>[
        'type'=>type::id(),
    ],
simplified
    'id'=>type::id()
```

### used in yii

#### Module support
it easy to integrate with GraphQLModuleTrait,the trait is responsible for initialization.
```php
     class Module extends Module{
        use GraphQLModuleTrait;
     }
```
in your config file:
```php
'modules'=>[
    'moduleName '=>[
       'class'=>'xxx\xxxx\module'
       //graphql config
       'schemas' => [        
          'query' => [
              'user' => 'app\graphql\query\UsersQuery'
          ],
          'mutation' => [
              'login'
          ],
          //if you use sample query except query contain interface,fragment,not need set
          //the key must same as your class definded
          'types'=>[          
              'Story'=>'yiiunit\extensions\graphql\objects\types\StoryType'
          ],
        ]                
    ],    
];
```
Use the controller to receive requests,access with GraphQLAction
```php
class xxxController extends Controller{
   function actions()
   {
       return [
            'index'=>[
                'class'=>'yii\graphql\GraphQLAction'
            ]
       ];
   }
}
```

#### Component Support
also you can include the trait with your own components,then initialization yourself.
```php
'components'=>[
    'componentsName '=>[
       'class'=>'xxx\xxxx\components'
       //graphql config
       'schemas' => [        
          'query' => [
              'user' => 'app\graphql\query\UsersQuery'
          ],
          'mutation' => [
              'login'
          ],
          //if you use sample query except query contain interface,fragment,not need set
          //the key must same as your class definded
          'types'=>[          
              'Story'=>'yiiunit\extensions\graphql\objects\types\StoryType'
          ],
        ]                
    ],    
];
```


### Input validation

Validation support is provided for data submission of mutation
In addition to graphql based validate, you can also use Yii Model validate, which is currently used for validation of input parameters. The rules method is added directly to the mutation definition
```php
public function rules()
    {
        return [
            ['password','boolean']
        ];
    }

```

### Authorization verification

Since graphql queries can be combined, such as when a query merges two query, and the two query have different authorization constraints, custom authentication is required in graph.
I refer to this query as "graphql actions"; when all graphql actions conditions are configured, it pass the authorization check.

#### Authenticate
In the behavior method of controller, the authorization method is set as follows
```php
function behaviors()
{
    return [
        'authenticator'=>[
            'class'=>'yii\graphql\filter\auth\CompositeAuth',
            'authMethods'=>[
                \yii\filters\auth\QueryParamAuth::className(),
            ],
            'except'=>['hello']
        ],
    ];
}
```
If you want to support IntrospectionQuery authorization, the corresponding graphql action is "__schema"

#### Authorization
if user has pass authenticate,you maybe want to check the access for the resource.you can use GraphqlAction's checkAccess
in controller where the action host.it will check all graphql actions.
```php
class GraphqlController extends Controller
{
    public function actions()
    {
        return [
            'index' => [
                'class' => 'yii\graphql\GraphQLAction',
                'checkAccess'=> [$this,'checkAccess'],
            ]
        ];
    }
    /**
     * authorization
     * @param $actionName
     * @throws yii\web\ForbiddenHttpException
     */
    public function checkAccess($actionName)
    {
        $permissionName = $this->module->id . '/' . $actionName;
        $pass = Yii::$app->getAuthManager()->checkAccess(Yii::$app->user->id,$permissionName);
        if(!$pass){
            throw new yii\web\ForbiddenHttpException('Access Denied');
        }
    }    
}
```

### Demo

#### Creating queries based on graphql protocols

Each query corresponds to a GraphQLQuery file
```php

class UserQuery extends GraphQLQuery
{
    public function type()
    {
        return GraphQL::type(UserType::class);
    }

    public function args()
    {
        return [
            'id'=>[
                'type'=>Type::nonNull(Type::id())
            ],
        ];
    }

    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        return DataSource::findUser($args['id']);
    }


}
```

Define type files based on query protocols
```php

class UserType extends GraphQLType
{
    protected $attributes = [
        'name'=>'user',
        'description'=>'user is user'
    ];

    public function fields()
    {
        $result = [
            'id' => ['type'=>Type::id()],
            'email' => Types::email(),
            'email2' => Types::email(),
            'photo' => [
                'type' => GraphQL::type(ImageType::class),
                'description' => 'User photo URL',
                'args' => [
                    'size' => Type::nonNull(GraphQL::type(ImageSizeEnumType::class)),
                ]
            ],
            'firstName' => [
                'type' => Type::string(),
            ],
            'lastName' => [
                'type' => Type::string(),
            ],
            'lastStoryPosted' => GraphQL::type(StoryType::class),
            'fieldWithError' => [
                'type' => Type::string(),
                'resolve' => function() {
                    throw new \Exception("This is error field");
                }
            ]
        ];
        return $result;
    }

    public function resolvePhotoField(User $user,$args){
        return DataSource::getUserPhoto($user->id, $args['size']);
    }

    public function resolveIdField(User $user, $args)
    {
        return $user->id.'test';
    }

    public function resolveEmail2Field(User $user, $args)
    {
        return $user->email2.'test';
    }


}
```

#### Query instance

```php
'hello' =>  "
        query hello{hello}
    ",

    'singleObject' =>  "
        query user {
            user(id:\"2\") {
                id
                email
                email2
                photo(size:ICON){
                    id
                    url
                }
                firstName
                lastName

            }
        }
    ",
    'multiObject' =>  "
        query multiObject {
            user(id: \"2\") {
                id
                email
                photo(size:ICON){
                    id
                    url
                }
            }
            stories(after: \"1\") {
                id
                author{
                    id
                }
                body
            }
        }
    ",
    'updateObject' =>  "
        mutation updateUserPwd{
            updateUserPwd(id: \"1001\", password: \"123456\") {
                id,
                username
            }
        }
    "
```

### Handle Exception

you can config the error formater for graph,the default handle use yii\graphql\ErrorFormatter,
it optimized processing of Model validation results
```php
'modules'=>[
    'moduleName '=>[
       'class'=>'xxx\xxxx\module'
       'errorFormatter'=>['yii\graphql\ErrorFormatter','formatError'],               
    ],    
];
```

### Future

* ActiveRecord generate tool for generating query and mutation class.
* Some of the special syntax for graphql,such as @Directives,has not test
