yii-graphql
==========
Using Facebook [GraphQL](http://facebook.github.io/graphql/) PHP server implementation. Extends [graphql-php](https://github.com/webonyx/graphql-php) to apply to YII2.

[![Latest Stable Version](https://poser.pugx.org/tsingsun/yii2-graphql/v/stable.svg)](https://packagist.org/packages/tsingsun/yii2-graphql)
[![Build Status](https://travis-ci.org/tsingsun/yii2-graphql.png?branch=master)](https://travis-ci.org/tsingsun/yii2-graphql)
[![Total Downloads](https://poser.pugx.org/tsingsun/yii2-graphql/downloads.svg)](https://packagist.org/packages/tsingsun/yii2-graphql)

--------

[Chinese document](/docs/README-zh.md)

-------

Features

* Configuration includes simplifying the definition of standard graphql protocols.
* Based on the full name defined by the type, implementing on-demand loading and lazy loading, and no need to define all type definitions into the system at load.
* Mutation input validation support.
* Provide controller integration and authorization support.

### Install

Using [composer](https://getcomposer.org/)
```
composer require tsingsun/yii2-graphql
```

### Type
The type system is the core of GraphQL, which is embodied in `GraphQLType`. By deconstructing the GraphQL protocol and using the [graph-php](https://github.com/webonyx/graphql-php) library to achieve fine-grained control of all elements, it is convenient to extend the class according to its own needs


#### The main elements of `GraphQLType`

The following elements can be declared in the `$attributes` property of the class, or as a method, unless stated otherwise. This also applies to all elements after this.

Element  | Type | Description
----- | ----- | -----
`name` | string | **Required** Each type needs to be named, with unique names preferred to resolve potential conflicts. The property needs to be defined in the `$attributes` property.
`description` | string | A description of the type and its use. The property needs to be defined in the `$attributes` property.
`fields` | array | **Required** The included field content is represented by the fields () method.
`resolveField` | callback | **function($value, $args, $context, GraphQL\Type\Definition\ResolveInfo $info)** For the interpretation of a field. For example: the fields definition of the user property, the corresponding method is `resolveUserField()`, and `$value` is the passed type instance defined by `type`.

### Query

`GraphQLQuery` and `GraphQLMutation` inherit `GraphQLField`. The element structure is consistent, and if you would like a reusable `Field`, you can inherit it.
Each query of `Graphql` needs to correspond to a `GraphQLQuery` object

#### The main elements of `GraphQLField`

 Element | Type  | Description
----- | ----- | -----
`type` | ObjectType | For the corresponding query type. The single type is specified by `GraphQL::type`, and a list by `Type::listOf(GraphQL::type)`.
`args` | array | The available query parameters, each of which is defined by `Field`.
`resolve` | callback | **function($value, $args, $context, GraphQL\Type\Definition\ResolveInfo $info)** `$value` is the root data, `$args` is the query parameters, `$context` is the `yii\web\Application` object, and `$info` resolves the object for the query. The root object is handled in this method.

### Mutation

Definition is similar to `GraphQLQuery`, please refer to the above.

### Simplified Field Definition

Simplifies the declarations of `Field`, removing the need to defined as an array with the type key.

#### Standard Definition

```php
//...
'id' => [
    'type' => Type::id(),
],
//...
```

#### Simplified Definition

```php
//...
'id' => Type::id(),
//...
```

### Yii Implementation

#### Module support

Can easily be implemented with `yii\graphql\GraphQLModuleTrait`. The trait is responsible for initialization.

```php
class MyModule extends \yii\base\Module
{
    use \yii\graphql\GraphQLModuleTrait;
}
```

In your application configuration file:

```php
'modules'=>[
    'moduleName ' => [
        'class' => 'path\to\module'
        //graphql config
        'schema' => [
            'query' => [
                'user' => 'app\graphql\query\UsersQuery'
            ],
            'mutation' => [
                'login'
            ],
            // you do not need to set the types if your query contains interfaces or fragments
            // the key must same as your defined class
            'types' => [
                'Story' => 'yiiunit\extensions\graphql\objects\types\StoryType'
            ],
        ],
    ],
];
```

Use the controller to receive requests by using `yii\graphql\GraphQLAction`

```php
class MyController extends Controller
{
   function actions() {
       return [
            'index'=>[
                'class'=>'yii\graphql\GraphQLAction'
            ],
       ];
   }
}
```

#### Component Support
also you can include the trait with your own components,then initialization yourself.
```php
'components'=>[
    'componentsName' => [
        'class' => 'path\to\components'
        //graphql config
        'schema' => [
            'query' => [
                'user' => 'app\graphql\query\UsersQuery'
            ],
            'mutation' => [
                'login'
            ],
            // you do not need to set the types if your query contains interfaces or fragments
            // the key must same as your defined class
            'types'=>[
                'Story'=>'yiiunit\extensions\graphql\objects\types\StoryType'
            ],
        ],
    ],
];
```


### Input validation

Validation rules are supported.
In addition to graphql based validation, you can also use Yii Model validation, which is currently used for the validation of input parameters. The rules method is added directly to the mutation definition.

```php
public function rules() {
    return [
        ['password','boolean']
    ];
}
```

### Authorization verification

Since graphql queries can be combined, such as when a query merges two query, and the two query have different authorization constraints, custom authentication is required.
I refer to this query as "graphql actions"; when all graphql actions conditions are configured, it passes the authorization check.

#### Authenticate

In the behavior method of controller, the authorization method is set as follows

```php
function behaviors() {
    return [
        'authenticator'=>[
            'class' => 'yii\graphql\filter\auth\CompositeAuth',
            'authMethods' => [
                \yii\filters\auth\QueryParamAuth::className(),
            ],
            'except' => ['hello']
        ],
    ];
}
```
If you want to support IntrospectionQuery authorization, the corresponding graphql action is `__schema`

#### Authorization

If the user has passed authentication, you may want to check the access for the resource. You can use `GraphqlAction`'s `checkAccess` method
in the controller. It will check all graphql actions.

```php
class GraphqlController extends Controller
{
    public function actions() {
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
    public function checkAccess($actionName) {
        $permissionName = $this->module->id . '/' . $actionName;
        $pass = Yii::$app->getAuthManager()->checkAccess(Yii::$app->user->id,$permissionName);
        if (!$pass){
            throw new yii\web\ForbiddenHttpException('Access Denied');
        }
    }
}
```

### Demo

#### Creating queries based on graphql protocols

Each query corresponds to a GraphQLQuery file.

```php
class UserQuery extends GraphQLQuery
{
    public function type() {
        return GraphQL::type(UserType::class);
    }

    public function args() {
        return [
            'id'=>[
                'type' => Type::nonNull(Type::id())
            ],
        ];
    }

    public function resolve($value, $args, $context, ResolveInfo $info) {
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

### Exception Handling

You can config the error formater for graph. The default handle uses `yii\graphql\ErrorFormatter`,
which optimizes the processing of Model validation results.

```php
'modules'=>[
    'moduleName' => [
       'class' => 'path\to\module'
       'errorFormatter' => ['yii\graphql\ErrorFormatter', 'formatError'],
    ],
];
```

### Future

* `ActiveRecord` tool for generating query and mutation class.
* Some of the special syntax for graphql, such as `@Directives`, has not been tested
