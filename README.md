# yii-graphql #

使用 Facebook [GraphQL](http://facebook.github.io/graphql/) 与 [React Relay](https://facebook.github.io/relay/). 扩展 [graphql-php](https://github.com/webonyx/graphql-php) 以适用于 YII2 ,当前还属于 **开发中**. 通过这个文档 [documentation](https://facebook.github.io/relay/docs/graphql-relay-specification.html#content) 你可以了解一些Relay的知识.

同时对Graphql-php的解构设计思路来源于 [laraval-graphql](https://github.com/Folkloreatelier/laravel-graphql).

为什么要解构graphql-php,该库主要是使graphql协议在php的实现,是不需要考虑在实际项目如何有更好的效率与性能的.包括laraval-graphql也是一样,离理想的生产应用还有差距.按需要定义及懒加载都未实现,必将异常配置文件的维护难度过大.

yii-graphql主要在使用的便利及加载方式做了较多优化.
1.配置最小化,包括简化标准graphql协议的定义.
2.按需要\懒加载,根据类型定义的全限定名,实现按需加载与懒,不需要在系统初始时将全部类型定义加载进入.
3.如果配合activerecord类型,relay对于表的查询操作十分简单.

todo
1.ActiveRecordType 导航属性的实现.
2.ActiveRecordType query schema的免定义
3.只针对activerecordType由于需要查询数据库定义,因此对该类型做了缓存,考虑通过压力测试看是否把全类型做缓存(最低优先级)
4.错误验证的返回

PS:对于graphql的一些特殊语法,像参数语法,接口语法,内置指令语法还未进行测试

### 安装 ###

本库位于私有库中,需要在项目composer.json添加库地址,目前还处理开发中
```php    
"require": {
    "tsingsun/yii2-graphql": "dev-master"
}
```

### Type ###
类型系统是GraphQL的核心,体现在GraphQLType中,通过解构graphql协议,并利用graph-php库达到细粒度的对所有元素的控制,方便根据自身需要进行类扩展.

GraphQLType的主要元素,** 注意元素并不对应到属性或方法中(下同) **

元素  | 类型 | 说明
----- | ----- | -----
name | string | **Required** 每一个类型都需要为其命名,如果能唯一是比较安全,但并不强制,该属性需要定义于attribute中
fields | array | **Required** 包含的字段内容,以fields()方法体现.
resolveField | callback | **function($value, $args, $context, GraphQL\Type\Definition\ResolveInfo $info)** 对于字段的解释,比如fields定义user属性,则对应的解释方法为resolveUserField() ,$value指定为type定义的类型实例

### Query ###

GraphQLQuery,GraphQLMutation继承了GraphQLField,元素结构是一致的,想做对于一些复用性的Field,可以继承它.
Graphql的每次查询都需要对应到一个GraphQLQuery对象

GraphQLField的主要元素

 元素 | 类型  | 说明
----- | ----- | -----
type | ObjectType | 对应的查询类型,单一类型用GraphQL::type指定,列表用Type::listOf(GraphQL::type)
args | array | 查询需要使用的参数,其中每个参数按照Field定义
resolve | callback | **function($value, $args, $context, GraphQL\Type\Definition\ResolveInfo $info)**,$value为root数据,$args即查询参数,$context上下文,为Yii的yii\web\Application对象,$info为查询解析对象,一般在这个方法中处理根对象

### Mutation ###

与GraphQLQuery是非常相像,参考说明.

### 简化处理 ###

简化了Field的声明,字段可直接使用type

```php
标准方式
    'id'=>[
        'type'=>type::id(),
    ],
简化写法
    'id'=>type::id()
```

### 在YII使用 ###

本组件采用trait的方式在Component组件中被引入，组件宿主建议的方式是Module
```php
     class Module extends Module{
        use GraphQLModuleTrait;
     }
```
配置文件
```php
'components'=>[
    'graphql'=>[
       'class'=>'xxx\xxxx\module'
       //主graphql协议配置
       'schemas' => [        
          'query' => [
              'user' => 'app\graphql\query\UsersQuery'
          ],
          'mutation' => [
              'login'
          ],
          //types一般不需要进行配置，当有特殊类型时
          'types'=>[
          ],
        ]                
    ],    
];
```
采用的是actions的方法进行集成
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

在采用动态解析的情况下,如果不想定义types时,schema的写法有讲究.可采用Type::class,避免采用Key方式,也方便直接通过IDE导航到对应的类下
```php
    'type'=>GraphQL::type(UserType::class)
```

### 授权验证

由于graphql查询是可以采用组合方式，如一次查询合并了两个query，而这两个query具有不同的授权约束，因此在graph中需要采用自定义的验证方式。
我把这多次查询查询称为graphql actions;当所有的graphql actions条件都满足配置时，才通过授权检查。

#### 授权
在controller的行为方法中设置采用的授权方法,例子如下，
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
如果要支持IntrospectionQueryr的授权，相应的graphql action为"__schema"

### Demo ###

#### 创建基于graphql协议的查询 ####

每次查询对应一个GraphQLQuery文件,
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

根据查询协议定义类型文件
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

#### 创建基于yii activerecord查询 ####

像普通的Query创建一个Query文件,只需要将type指向模型文件即可
```php
class UserModelQuery extends GraphQLQuery
{
    public function type()
    {
        return Type::listOf(GraphQL::type(UserModel::class));
    }

    public function args()
    {
        return [
            'id'=>[
                'type'=>Type::id(),
                'description'=>'用户的ID'
            ],
            'pageIndex'=>[
                'type'=>Type::int(),
                'description'=>''
            ],
            'pageSize'=> [
                'type'=> Type::int(),
                'description'=>'',
            ],
        ];
    }

    public function resolve($root,$args,$context,ResolveInfo $info){
        $id = $args['id'];
        return UserModel::findAll(['id'=>$id]);
    }

}
```

#### 查询实例 ####

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

### 深入了解 ###

有必要了解一些graphql-php的相关知识,这部分git上的文档相对还少些,需要对源码的阅读.下面列出重点

#### DocumentNode (语法解构) ####

```
array definitions
    array OperationDefinitionNode
        string kind
        array NameNode
            string kind
            string value
```
