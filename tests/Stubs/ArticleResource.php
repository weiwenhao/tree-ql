<?php

namespace Weiwenhao\Including\Tests\Stubs;

use Weiwenhao\Including\Resource;

class ArticleResource extends Resource
{
    protected $baseColumns = ['id', 'title', 'slug', 'description', 'like_count'];

    protected $includeColumns = ['word_count', 'read_count', 'give_count'];

    protected $includeRelations = [
        'comments'
    ];

    // 手动处理
    protected $includeMeta = [];
    protected $includeOther = ['liked'];

    public function getLiked()
    {
    }
}
