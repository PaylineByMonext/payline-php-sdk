<?php

namespace test\Payline\cache;

use Payline\Cache\Apc;
use PHPUnit\Framework\TestCase;

class ApcTest extends TestCase
{

    public function testIsAvailable()
    {
        $mockApc = $this->getMockBuilder(Apc::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->assertFalse($mockApc->isAvailable());

    }
}
