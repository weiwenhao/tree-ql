<?php

namespace Weiwenhao\Including\Tests\Stubs;

use Weiwenhao\Including\Resource;

class UserResource extends Resource
{
    protected $baseColumns = ['id', 'nickname', 'avatar'];
    protected $includeColumns = ['password', 'phone_number'];

    protected $includeRelations = [
        'role' => [
            'resource' => RoleResource::class
        ],
    ];
}
