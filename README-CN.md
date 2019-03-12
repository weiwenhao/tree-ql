## 什么是tree-ql?

tree-ql是一个**laravel扩展**,通过**简单的配置**构建出一套**极具描述性**,可读性,且没有任何冗余的**高性能**API.

- 不具入侵性之laravel,可以随时集成在已有的laravel项目中.
- 不具入侵性之RESTful,基于RESTful进一步提升API描述性
- 无论多么复杂的API描述,都不会产生**N + 1**问题.

- tree-ql并不是一套API规范,而是一套生产环境解决方案,能够**提高开发效率**.
- **所见即所得**,客户端可以根据自己的需求来include所需的资源,而不产生冗余.



#### 基本描述

![](http://asset.eienao.com/20190311113622.png)



#### 简单描述

![](http://asset.eienao.com/20190311113711.png)



#### 复杂描述

![](http://asset.eienao.com/20190311114049.png)



## 安装

确保你的laravel版本在5.5以上,在项目根目录下执行

```bash
composer require weiwenhao/tree-ql
```

> 当前版本号 < 1.0.0, 属于alpha版本.



## 文档

### include

可以看到,tree-ql的核心即include,那么include的语法规则是怎样的呢?

![](http://asset.eienao.com/20190311135637.png)

可以看到include语法较为简单,补充两点

- `.` 和`{}` 都能表示层级嵌套关系, `.`是`{}` 中只嵌套一个字段时的语法糖, 如果需要嵌套多个字段时,请使用`{}`

- 层级嵌套理论上是无限的(受限于url的长度),当然只要你按照tree-ql的语法规范,无论多么深的嵌套都不会有性能问题



### 简单配置

前面一直在介绍tree-ql的功能与外在表现,现在来介绍一下tree-ql的内在配置,首先看看配置的入口文件.

```php
# app/Resources/PostResource

<?php

namespace App\Resources;

use Weiwenhao\TreeQL\Resource;

class PostResource extends Resource
{
    protected $default = [
        'id',
        'title',
        'user_id'
    ];

    protected $columns = [
        'id',
        'title',
        'user_id'
    ];

    protected $relations = [
        'comments',
        'user'
    ];

    protected $custom = [
        'liked'
    ];
    
    protected $meta = [
        
    ];
}

```



从前端的角度来看include中描述的是一个个无差别的字段, 如 user,content,liked等等,但是从后端的角度,我们会将能够include字段分为四种类型.也就是上面代码段中的四个属性`columns/relations/custom/meta`,这四种类型覆盖了我们日常api编写过程中所涉及的所有字段.

#### columns

其就是数据库中的column.

什么时候需要include的column?

从SQL语句的角度来看, `select *` 是非常不推荐的一种写法,其会有不可控的性能问题和字段冗余问题.最常见的例子就是我们帖子的content.通常获取帖子列表的SQL语句中,我们并不会取出content. 只有展示帖子详情时会取出content

对于这种情况, 过去有一种做法是 `test.com/api/posts/{post}?fields=id,title,content`. 但是在tree-ql中column可以通过include来控制,在避免了 `select *`问题的同时,更具有一致性.



#### relations

relations既laravel中的[模型关联](https://learnku.com/docs/laravel/5.7/eloquent-relationships/2295#defining-relationships), 其对应Model中的关联方法.

什么时候需要include的relation?

在帖子列表的场景中. 在pc端由于展示位置充足,通常会展示作者信息.但是在android/ios中展示位置较小,通常不会展示作者信息.这里的作者属于帖子,即 belogs to user 的relation关系.

过去可以编写一条通用的api(一直携带作者信息)来适配两端,或者分别编写两条api来适配pc和android/ios,现在我们有更好的做法,让客户端根据实际情况来决定是否需要携带作者信息

即 `pc: test.com/api/posts?include=user`, `android/ios: test.com/api/posts`

**注意,由于user属于relation,所以需要为user定义相关的Resource.** **默认配置下,`PostsResource`会去关联`app/Resources/UserResource`**.当然你可以在自己指定user对应的Resource

```php
protected $relations = [
    'comments',
    'user' => CustomUserResource::class,
];
```

UserResource的定义

```php
# UserResource.php

<?php

namespace App\Resources;

use Weiwenhao\TreeQL\Resource;

class UserResource extends Resource
{
    protected $default = ['id', 'nickname', 'avatar'];

    protected $columns = ['id', 'nickname', 'avatar', 'password'];
}

```

以及相应的关联关系

```php
# Post.php

<?php

namespace App\Models;

class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

有了上线的定义我们就可以愉快的`include=user`啦

> 当relation中定义的字段由多单词组成时  `$relations = ['custom_relation']` 定义使用下划线分词.关联方法则使用小驼峰既 `customRelation()`
>
> 总而言之,只要是定义在relation中字段,都需要相应的Resource和Model关联关系支持



#### custom

不存放在数据库中,通过一些规则计算得来的column.

什么时候需要include的custom?

在帖子列表的某些场景中,会展示当前用户是否点赞过该帖子,这是一个耗费性能的计算.

 ```json
{
    "id": 1,
    "title": "...",
    "liked": true,
}
 ```

客户端喜欢这样易于绑定的数据结构,但帖子表中并不会存在一个liked字段来记录你是否点赞过该帖子.

此时custom就派上了用场,就像上面$custom中定义一样. 但是还差了点什么?

 ```php
protected $custom = [
    'liked' => function ($post) {
        // logic 
        return $bool;
    }
];
 ```

是的,我们还差一个回调,来判断当前登录用户是否点赞过该帖子. 但是php并不支持上面的写法.所以在tree-ql中我们需要将回调抽离出来.

 ```php
protected $custom = [
    'liked'
];

public function liked($post, $params)
{
    // logic 
    // return $bool;
}
 ```

这就是custom的使用场景,以及回调的用法了,客户端只需要通过 `test.com/api/posts?include=liked`就会触发该回调,并返回相应的结果.

>  当custom中定义的字段由多单词组成时  `$custom = ['custom_test']` 定义使用下划线分词.回调方法则使用小驼峰既 `customTest($item, $params)`



#### meta

必须说明的一点是tree-ql为数据的最外层包裹了data和meta.我认为这是有必要的, 比如在含有分页的场景中,我们可以把分页的信息放在meta中

```json
{
  "data": [
    {
      "id": 1,
      "title": "Aperiam quisquam porro fugiat et in itaque",
      "user_id": 1
    },
  ],
  "meta": {
    "pagination": {
      "per_page": 15,
      "total": 100,
      "current": 1,
      "next": "http://test.com/api/posts?page=2",
      "previous": null,
      "last": 7
    }
  }
}
```

同样的,我们可以根据业务需求定义一些附加信息在meta中,并设置回调(与custom的回调操作一致,但回调参数只有params),让客户端去include.

> 这里的pagination并不需要主动的include, tree-ql会为你维护该数据
>
> 当meta中定义的字段由多单词组成时  `$meta = ['custom_meta']` 定义使用下划线分词.回调方法则使用小驼峰既 `customMeta($params)`



#### default

default中的定义从 `columns/relations/custom/meta` 中获得,且default中的定义其会被默认include进来,而不需要在url中显式的include进来



#### 资源嵌套

![](http://asset.eienao.com/20190311171337.png)

虽然我们的Resource中存在4种类型的配置,但只有relation类型的字段允许使用`.`和`{}`语法进行资源嵌套.

`test.com/api/posts?include=comments{user}`  

#### params

![](http://asset.eienao.com/20190311171731.png)

![](http://asset.eienao.com/20190311171848.png)

这就是params的语法规则.上面已经介绍过,custom和meta拥有相应的回调函数,因此params也会被传入到回调函数中

```php
protected $custom = [
    'liked'
];

protected $meta = [
    'test'
];
 
/**
 * @param $post
 * @param $params ["key1" => "value1", "key2" => "value2"]
 */
public function liked($post, $params)
{
    // logic
    // return
}

/**
 * @param $params ["key1" => "value1", "key2" => "value2"]
 */
public function test($params)
{
    // logic
    // return
}
```



还有一类情况就是relation params. relation的load由tree-ql负责,并没有类似custom的回调函数. 但是在load时,预留了相应的回调

如 `test.com/api/posts/{post}?include=comments(sort_by:floor)`那么相应的回调是

```php
# CommentResource.php

<?php

namespace App\Resources;

use Weiwenhao\TreeQL\Resource;

class CommentResource extends Resource
{
    protected $default = ['id', 'content', 'user_id', 'floor'];

    protected $columns = ['id', 'content', 'user_id', 'floor'];

    protected $relations = [
        'user',
        'replies' => [
            'resource' => CommentReplyResource::class,
        ]
    ];

    
    /**
     * test.com/api/posts/{post}?include=comments(sort_by:floor)
     *
     * sound code ↓ ↓ ↓
     *
     * $posts->load(['comments' => function ($builder) {
     *      $this->loadConstraint($builder, ['sort_by' => 'floor']);
     * });
     *
     * ↓ ↓ ↓
     *
     * @param $builder
     * @param array $params
     */
    public function loadConstraint($builder, $params)
    {
        isset($params['sort_by']) && $builder->orderBy($params['sort_by'], 'desc');
    }
}

```

include的语法规则和tree-ql所有的配置规则已经介绍完毕了, 虽然花了很长的篇幅来介绍,但是大部分篇幅都用来介绍使用场景,实际配置还是非常简单的.



### 使用

来看看PostController,tree-ql的使用就一目了然了

```php
/**
 * test.com/api/posts?include=xxx 
 * 只需要把posts传递给PostResource,其中的定义和配置就被激活了!!
 *
 * @return \Weiwenhao\TreeQL\Resource
 */
public function index()
{
    // $posts = Post::columns()->latest()->get(); 同样支持
    $posts = Post::columns()->latest()->paginate();

    // 等价于 return PostResource::make($post, request('include'))
    return PostResource::make($posts);
}

/**
 * @param  \App\Models\Post $post
 * @return \Weiwenhao\TreeQL\Resource
 */
public function show($post)
{
    return $resource = PostResource::make($post);
}
```

这里唯一要讲解的就是columns()这个查询构造器,其由tree-ql提供. 会根据PostResource中定义的column和default,并结合实际的include来为Builder添加合适的select(), 而不是`select *`



## License

This project is licensed under the MIT License