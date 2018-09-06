<?php

namespace Weiwenhao\Including\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
