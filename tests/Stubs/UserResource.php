<?php

namespace Weiwenhao\TreeQL\Tests\Stubs;

use Weiwenhao\TreeQL\Resource;

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
