<?php

namespace Weiwenhao\Included\Tests\Stubs;

use Weiwenhao\Included\Resource;

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
