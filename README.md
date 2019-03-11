English | [简体中文](https://github.com/weiwenhao/tree-ql/blob/master/README-CN.md)

## What is tree-ql?

tree-ql is a **laravel extension** that builds a **highly descriptive**, readable, **high-performance** API with no redundancy through a **simple configuration**.

- **Will not invade laravel**, can be integrated into the existing laravel project at any time.
- **Will not invade RESTful** and further improve API descriptiveness based on RESTful.
- No matter how complex the API description, **no N + 1 problem** will occur.

- Tree-ql is not a set of API specifications, but a production environment solution that can **improve development efficiency**.
- **WYSIWYG**, the client can include the required resources according to their own needs without redundancy.



#### Basic description

![](http://asset.eienao.com/20190311113622.png)



#### Quick description

![](http://asset.eienao.com/20190311113711.png)



#### Complex description

![](http://asset.eienao.com/20190311114049.png)



## Installation

Make sure your laravel version is above 5.5 and execute in the project root directory

```bash
composer require weiwenhao/tree-ql
```

> The current version number < 1.0.0, which belongs to the alpha version.



## Document

### include

You can see that the core of tree-ql is include, so what is the grammar rule of include?

![](http://asset.eienao.com/20190311135637.png)

You can see that the include syntax is simpler, add two points.

- Both` . `and` {}` can represent hierarchical nesting relationships, which are syntactic sugars when only one field is nested in `{}`. If you need to nest multiple fields, use` {}`

- Hierarchical nesting is theoretically infinite (limited by the length of the url), of course, as long as you follow the syntax of tree-ql, no matter how deep the nesting, there will be no performance problems.



### Simple configuration

I have been introducing the function and external performance of tree-ql. Now let me introduce the internal configuration of tree-ql. First, let's look at the configuration entry file.

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

From the perspective of the front-end, the description describes the fields that are indistinguishable, such as user, content, liked, etc., but from the perspective of the backend, we will divide the include fields into four types. That is, the above code segment The four attributes `column/relations/custom/meta`, which cover all the fields involved in our daily api writing process.



#### columns

It is the column in the database.

When do you need to include the column?

From the perspective of SQL statements, `select *` is a very uncommitted way to write, it has uncontrollable performance problems and field redundancy problems. The most common example is the content of our post. Usually get the SQL list of the post list In the middle, we will not take out the content. The content will be retrieved only when the post details are displayed.

In this case, there is a practice in the past is `test.com/api/posts/{post}?fields=id,title,content`. But in tree-ql column can be controlled by include, avoiding the problem of `select *` At the same time, it is more consistent.



#### relations

Relations are [Relationships](https://laravel.com/docs/5.8/eloquent-relationships) in laravel, which correspond to association methods in Model.

When do you need to include the relation?

In the scene of the post list. On the pc side, due to the sufficient placement, the author information is usually displayed. However, the display position in android/ios is small, and the author information is usually not displayed. The author here belongs to the post, ie belogs to user Relationship relationship.

In the past, you can write a generic api (always carry author information) to adapt to both ends, or write two apis to adapt pc and android/ios. Now we have a better way to let the client decide according to the actual situation. Do you need to carry author information?

which is `pc: test.com/api/posts?include=user`, `android/ios: test.com/api/posts`

**Note that since user belongs to relation, you need to define the related Resource for user. By default, PostsResource will associate app/Resources/UserResource.** Of course you can specify user corresponding resource.

```php
protected $relations = [
    'comments',
    'user' => CustomUserResource::class,
];
```

UserResource definition

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

And the corresponding relationship

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

With the definition of the online, we can have a happy `include=user`

> When the field defined in relation consists of multiple words `$relations = ['custom_relation']` defines the underlined word segmentation. The association method uses the small hump both `customRelation()`
>
> In summary, as long as the field defined in the relation, you need the corresponding Resource and Model relationship support.



#### custom

It is not stored in the database, and the column is calculated by some rules.

When do you need to include custom?

在帖子列表的某些场景中,会展示当前用户是否点赞过该帖子,这是一个耗费性能的计算.

 ```json
{
    "id": 1,
    "title": "...",
    "liked": true,
}
 ```

The client likes this easy-to-bind data structure, but there is no liked field in the post table to record whether you liked the post.

At this point custom has come in handy, as defined in $custom above. But what's worse?

 ```php
protected $custom = [
    'liked' => function ($post) {
        // logic 
        return $bool;
    }
];
 ```

Yes, we still have a callback to determine whether the current login user liked the post. But php does not support the above. So in tree-ql we need to pull the callback out.

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

This is the use of custom, and the use of callbacks, the client only needs to pass `test.com/api/posts?include=liked` will trigger the callback, and return the corresponding results.

>  When the field defined in custom consists of multiple words `$custom = ['custom_test']` defines the underlined word segmentation. The callback method uses the small hump both `customTest($item, ​$params)`



#### meta

One point that must be explained is that tree-ql wraps data and meta for the outermost layer of data. I think this is necessary, for example, in a scene with paging, we can put the paging information in meta.

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

Similarly, we can define some additional information in the meta according to business needs, and set the callback (consistent with the custom callback operation, but the callback parameter is only params), let the client to include.

> The pagination here does not require active include, tree-ql will maintain this data for you.
>
> When the field defined in meta is composed of multiple words, `$meta = ['custom_meta']` defines the underlined word segmentation. The callback method uses the small hump both `customMeta($params)`



#### default

The definition in default is obtained from `columns/relations/custom/meta`, and the definition in default is included by default, without the need to explicitly include it in the url.



#### Resource nesting

![](http://asset.eienao.com/20190311171337.png)

Although there are 4 types of configurations in our Resource, only the fields of the relation type allow resource nesting using the `.` and `{}` syntax.

`test.com/api/posts?include=comments{user}`  

#### params

![](http://asset.eienao.com/20190311171731.png)

![](http://asset.eienao.com/20190311171848.png)

This is the syntax of params. As mentioned above, custom and meta have corresponding callback functions, so params are also passed to the callback function.

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



There is also a type of situation where relational params. relation load is responsible for tree-ql, and there is no custom callback function. But in load, the corresponding callback is reserved.

For example, `test.com/api/posts/{post}?include=comments(sort_by:floor)` then the corresponding callback is

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

The grammar rules of include and all the configuration rules of tree-ql have been introduced. Although it takes a long time to introduce, most of the space is used to introduce the use of the scene, the actual configuration is very simple.

### Use

Take a look at PostController, the use of tree-ql is clear at a glance.

```php
/**
 * test.com/api/posts?include=xxx 
 * Just pass the posts to PostResource, and the definitions and configurations are activated!!
 *
 * @return \Weiwenhao\TreeQL\Resource
 */
public function index()
{
    // $posts = Post::columns()->latest()->get(); Same support
    $posts = Post::columns()->latest()->paginate();

    // Equivalent to return PostResource::make($post, request('include'))
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

The only thing to explain here is the column() query constructor, which is provided by tree-ql. It will add the appropriate select() to the Builder according to the column and default defined in PostResource, combined with the actual include, instead of `select *`



## License

This project is licensed under the MIT License