<?php

namespace Weiwenhao\Included\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
