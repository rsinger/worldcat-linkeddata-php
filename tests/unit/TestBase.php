<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;

class TestBase extends TestCase
{
    protected function setUp()
    {
        $className = get_class($this);
        $testName = $this->getName();
        echo " Test: {$className}->{$testName}\n";
        parent::setUp();
    }
}
