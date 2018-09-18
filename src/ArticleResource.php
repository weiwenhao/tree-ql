<?php

namespace Weiwenhao\Including;

class ArticleResource extends Resource
{
    protected $baseColumns = ['id', 'title', 'slug', 'description', 'like_count'];

    // 自动处理
    protected $includeColumns = ['word_count', 'read_count', 'give_count'];

    protected $includeRelations = ['user' => [
        'alias' => 'author',
        'default' => true,
        'resource' => Resource::class
    ], 'tags'];

    // 手动处理
    protected $includeMeta = [];
    protected $includeOther = ['liked'];
}
