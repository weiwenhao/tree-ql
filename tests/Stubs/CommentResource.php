<?php

namespace Weiwenhao\Including\Tests\Stubs;

use Weiwenhao\Including\Resource;

class CommentResource extends Resource
{
    protected $baseColumns = ['id', 'content', 'created_at'];
    protected $includeColumns = ['like_count'];

    protected $includeRelations = [
        'user' => [
            'resource' => UserResource::class
        ],
    ];
}
