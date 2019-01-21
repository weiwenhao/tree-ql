## 什么是TreeQL

tree-ql 是一个laravel扩展,希望能够从url中include你所需的资源,实现查询的所见既所得.

```json
// http://api.test/posts/{slug}?include=content,user,comments  ↓

{
    "data": {
        "id": 1,
        "slug": "quisquam-asperiores-est-necessitatibus-et.",
        "title": "Quisquam asperiores est necessitatibus et.",
        "description": "Officiis nihil sunt ut veritatis.",
        "cover": "https://lorempixel.com/640/480/?63535",
        "comment_count": 11,
        "like_count": 11,
        "content": "Omnis quisquam dolorem quasi sequi veritatis quia dolorem sed. Ut non voluptatem beatae eum. ",
        "comments": [
          {
            "id": 303,
            "content": "Quasi dignissimos dolor tempore exercitationem.",
            "user_id": 2481,
            "post_id": 1,
            "like_count": 18,
            "reply_count": 9,
            "floor": 303
          }
        ],
        "user": {
          "id": 1221,
          "nickname": "Ashleigh McKenzie",
          "avatar": "https://lorempixel.com/640/480/?29515"
        }
    }
}
```

更加深入的使用

```json
// http://api.test/posts/{slug}?include=
// content,user,comments(sort_by:like_count){user,replies.user},is_like,select_comments

{
  "data": {
    "id": 1,
    "slug": "quisquam-asperiores-est-necessitatibus-et.",
    "title": "Quisquam asperiores est necessitatibus et.",
    "description": "Officiis nihil sunt ut veritatis.",
    "cover": "https://lorempixel.com/640/480/?63535",
    "comment_count": 11,
    "like_count": 11,
    "user_id": 1221,
    "content": "Omnis quisquam dolorem quasi sequi veritatis quia dolorem sed. Ut non voluptatem beatae eum.",
    "is_like": true,
    "comments": [
      {
        "id": 303,
        "content": "Quasi dignissimos dolor tempore exercitationem.",
        "user_id": 2481,
        "post_id": 1,
        "like_count": 18,
        "reply_count": 9,
        "floor": 303,
        "user": {
          "id": 2481,
          "nickname": "Garett O'Connell",
          "avatar": "https://lorempixel.com/640/480/?52652"
        },
        "replies": [
          {
            "id": 415,
            "comment_id": 303,
            "user_id": 2814,
            "content": "Odit magnam sed ut.",
            "call_user": null,
            "created_at": "2018-12-12 02:26:08",
            "updated_at": "2018-12-12 02:26:08",
            "user": {
              "id": 2814,
              "nickname": "Ted Dickinson",
              "avatar": "https://lorempixel.com/640/480/?19577"
            }
          }
        ]
          
      }
    ],
    "user": {
      "id": 1221,
      "nickname": "Ashleigh McKenzie",
      "avatar": "https://lorempixel.com/640/480/?29515"
    }
  },
  "meta": {
    "selected_comments": [
      {
        "id": 303,
        "content": "Quasi dignissimos dolor tempore exercitationem.",
        "user_id": 2481,
        "post_id": 1,
        "like_count": 18,
        "reply_count": 9,
        "floor": 303,
        "selected": 1,
        "created_at": "2018-12-12 02:25:55",
        "updated_at": "2018-12-12 02:25:55",
        "user": {
          "id": 2481,
          "nickname": "Garett O'Connell",
          "avatar": "https://lorempixel.com/640/480/?52652"
        }
      }
    ]
  }
}


```

你可能会发现和GraphQL比起来并不是真正的所见既所得,这是由于http请求url长度的限制,所以加入了default的概念, TreeQL会结合include和default来返回相应的资源.



## 安装

确保你的laravel版本在5.5以上,在项目目录下执行

`composer require weiwenhao/tree-ql`

> 该版本目前为alpha版本,不推荐用于商业生产环境,推荐用于个人项目

## 使用

由于tree-ql是一个laravel的扩展包,接下来会从laravel的角度进行切入,实际上如果你熟悉 dingo/api的include,你会更加适应这种开发模式.

#### 我可以include什么东西?

由于include所见即所得,因此可以换个提问方式,我的response中可以返回些什么数据?

response中的数据可以分为4类, 既 **columns,relations,each,meta.** 

columns 既我们数据库中的columns, 如 id,name,created_at,updated_at等

relations 既orm中的关联关系, 比如post资源的relation有一对一的 user,一对多的 comments, 具体的定义都在laravel的model中定义

each 可以理解为没有存储在mysql中,由程序员计算得来的column, 其和column是平级的, 比如 一个user是否点赞了一篇post, 那么在我们的post的response中可能会见到这样的数据 ↓

```json
{
    "data": [
        {
            "id": 1,
            "slug": "quisquam-asperiores-est-necessitatibus-et.",
            "title": "Quisquam asperiores est necessitatibus et.",
            "description": "Officiis nihil sunt ut veritatis.",
            "is_like": true // 该字段由程序员计算得来, 没有也不能存储在数据库中
    	},
        {
            "id": 2,
            "slug": "quisquam-asperiores-est-necessitatibus-et.",
            "title": "Quisquam asperiores est necessitatibus et.",
            "description": "Officiis nihil sunt ut veritatis.",
            "is_like": false
    	}
    ]
}
```

meta 用来存储一些无法存储在data中的数据, 最典型的例子既分页信息 ↓

```json
{
  "data": [
    {
      "id": 285,
      "slug": "repellat-illo-molestias-quidem-ea-autem.",
      "title": "Repellat illo molestias quidem ea autem.",
      "description": "Sed harum.",
      "cover": "https://lorempixel.com/640/480/?12347",
      "comment_count": 8,
      "like_count": 14,
      "user_id": 2023
    }
    // ...
  ],
  "meta": {
    "pagination": {
      "per_page": 15,
      "total": 300,
      "current": 1,
      "next": "http://api.jianshu.test/api/posts?page=2",
      "previous": null,
      "last": 20
    }
  }
}
```

接下来看看如何在laravel中进行定义

#### Resource的定义

tree-ql默认使用app下的Resources目录, 因此可能会有这样的目录结构

![](http://asset.eienao.com/20190118180524.png)



接下来以PostResource为例

```php
<?php

namespace App\Resources;

use Weiwenhao\TreeQL\Resource;

class PostResource extends Resource
{
    /**
    * 从下面的 columns/relations/meta/each中抽取得来
    */
    protected $default = [
        'id',
        'slug',
        'title',
        'description',
        'cover',
        'comment_count',
        'like_count',
        'user_id'
    ];

    protected $columns = [
        'id',
        'slug',
        'title',
        'description',
        'cover',
        'comment_count',
        'like_count',
        'user_id',
        'content'
    ];

    protected $relations = [
        'user',
        'comments',
    ];

    protected $meta = [
        'selected_comments'
    ];

    protected $each = ['is_like'];

    public function isLike($item, $params)
    {
        return array_random([true, false]);
    }

    public function selectedComments($params)
    {
        $post = $this->getCollection()->first();

        $comments = $post->selectedComments;

        $resource = CommentResource::make($comments, 'user,replies.user');

        return $resource->getResponseData();
    }
}
```

Resource分为两部分, **类属性部分**用来进行定义,除了default外,其余部分 columns/relations/meta/each 中定义的value 都可以在include中被引入.

而default中的定义则是从 columns/relations/meta/each 中已经定义的value进行抽取,default中的key,会被默认include进来,而不需要再url中显式的定义.

**方法部分** 目前的作用主要是回调函数, 且只有each和meta中定义的value 需要callback. callback命名的规则也很简单, 既将meta或者each中定义的值改为 小驼峰命名 作为方法名称即可.

each的callback有两个参数,  每一个resource下都有一个collection属性, 其中存放了该Resource下的资源数据, 其类型为`Illuminate\Database\Eloquent\Collection` ,collection中的每一个item都会被callback一次, 所以 上面 isLike的第一个参数为 Collection中的一个item, item既model 

> 在Resource中通过调用 $this->getCollection()可以获取所有的数据

由于include支持params, 所以isLike的第二个参数为include中传递的params, 类型为array,格式为

```php
$params = [
    'sort_by' => 'created_at',
    'order' => 'desc'
]
```

callback 中 return的值将会在response data中被原样展示

**关于meta**

meta不同于each, 每个include meta在其生命周期中只会被调用一次.且只有一个参数 既params. 其return的值也将在response meta中被原样展示

meta的另一个特点时,只有最外层的数据结构才存在meta, 即

```json
{
    data: {},
    meta: {}
}
```

因此如果在更深层次的resource中进行include meta 那么会产生的行为时, 该meta数据,被拉到了最外层. 举个例子

`include=meta1,post.meta2`  那么返回的结果是

```json
{
    data: {
        post: {}
    },
    meta: {
        meta1: {},
        meta2: {}
    }
}
```

这个行为我并不是很喜欢,所以在考虑更加合适的解决方案



**Columns** 中定义了orm select语句中可以被查询的数据,既类似这样的行为会使用columns

![](http://asset.eienao.com/20190121115105.png)



#### 使用Resource

接下来看看PostController的index和show方法

```php
	/**
     * Display a listing of the resource.
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
     * Display the specified resource.
     *
     * @param  \App\Models\Post $post
     * @return \Weiwenhao\TreeQL\Resource
     */
    public function show($post)
    {
        return $resource = PostResource::make($post);
    }
```

上面的使用非常的简单, 唯一需要讲解的便是 columns() 这个查询构造器. 我不希望Post的查询一下查询出table中所有的column,而是根据url中include进行查询. 所以columns()会解析url中include并结合resource中的定义进行合适的select.

#### include的语法规则

已实例进行讲解

`http://api.test/posts/{slug}?include=user`  基础使用,在post的基础上 include 这篇post的作者

**我想include PostResource中定义的更多的东西怎么办?**

`http://api.test/posts/{slug}?include=user,content,comments` 使用逗号进行分割

**我想引入comment中的user怎么办?**

`http://api.test/posts/{slug}?include=user,content,comments.user  `使用`.`进行嵌套

**我想同时引入comment中的user和replies怎么做?**

`http://api.test/posts/{slug}?include=user,content,comments{user,replies}  `  使用 `{}` 和 `,` 来代替`.`语法进行嵌套

> 在dingo/api中 你可能需要这么做  `include=comment.user,comment.replies`

**我想对include的comments添加一些条件我应该怎么做?**

`http://api.test/posts/{slug}?include=user,content,comments(sort_by:created_at,order:desc){user,replies}`  条件语法紧跟着comments, `()`中包围的既params,  形式为 `key1:value1,key2:value2`

> 实际上 目前只有 each和meta支持回调.  后续会对columns和relations添加回调.到时params将会有更强大的作用

**这就是 include的所有语法规则了, 理论上所有的语法规则都支持无限嵌套与任意组合**

比如 `include=a,b.c.d,c{b},c{b(f:b),a.b.c},c(b.a),c{f,b}.b(a:b).c` 



## 下一步的计划

- 为column 和 relations添加回调.

- 添加单元测试

- 为定义添加一些基础功能 比如

  ```php
  protected $relations = [
      'user' => [
          'resource' => UserResource::class,
          'alias' => 'vip',
          'builder' => function ($builder, $params) {
              $builder->orderBy($params['sort_by'], $params['order'])
          }
      ],
      'comments' => [
          'alias' => 'test_comment',
          'auth' => function ($version) {
              return true; // or false
          }
      ]
  ];
  ```

  > php并不支持回调式的写法,所以需要计划一下解决方案, 以及引入哪些基本功能

- 添加中文及英文文档



实际上在几个月前,该项目就基本完成了.受到工作影响搁置,最后一点收尾始终无法完成,我不想这个项目付诸东流,所以在年前赶一波进度.

希望大家发表自己的想法与意见,争取尽快发布1.0版本,在商业项目上有用武之地
