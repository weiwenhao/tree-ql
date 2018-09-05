<?php

namespace Weiwenhao\Included\Tests;

use Weiwenhao\Included\Tests\Stubs\ExampleResource;

class ResourceTest extends TestCase
{
    protected $resource;

    /**
     * include=article{word_count,read_count,comment{like_count,user}},user.role,tags,content
     */
    public function setUp()
    {
        parent::setUp();

        $this->resource = new ExampleResource();
    }


    public function test_structure_tree()
    {
        $include = [
            'article' => [
                'word_count',
                'read_count',
                'comments' => [
                    'like_count',
                    'user'
                ],
            ],
            'tags',
            'content',
            'user' => [
                'role'
            ]
        ];
        $tree = $this->resource->structureTree($include);
        $expected = [
            'columns' => ['id', 'description', 'content'],
            'meta' => [],
            'other' => [],
            'relations' => [
                'article' => [
                    'columns' => [
                        'id',
                        'title',
                        'slug',
                        'description',
                        'like_count',
                        'word_count',
                        'read_count'
                    ],
                    'meta' => [],
                    'other' => [],
                    'relations' => [
                        'comments' => [
                            'columns' => ['id', 'content', 'created_at', 'like_count'],
                            'meta' => [],
                            'other' => [],
                            'relations' => [
                                'user' => [
                                    'columns' => ['id', 'nickname', 'avatar'],
                                    'meta' => [],
                                    'other' => [],
                                    'relations' => []
                                ]
                            ]
                        ]
                    ]
                ],
                'user' => [
                    'columns' => ['id', 'nickname', 'avatar'],
                    'meta' => [],
                    'other' => [],
                    'relations' => [
                        'role' => [
                            'columns' => ['id', 'name'],
                            'meta' => [],
                            'other' => [],
                            'relations' => []
                        ]
                    ]
                ],
                'tags' => [
                    'meta' => [],
                    'columns' => ['id', 'name'],
                    'relations' => [],
                    'other' => [],
                ]
            ]
        ];

        $this->assertEquals($expected,$tree);
    }
}
