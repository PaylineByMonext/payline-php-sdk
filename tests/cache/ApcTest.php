<?php

namespace Cache;

use Payline\Cache\Apc;
use PHPUnit\Framework\TestCase;

class ApcTest extends TestCase
{

    public function testIsAvailable()
    {
        $mockApc = $this->getMockBuilder(Apc::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertNull($mockApc->isAvailable());

    }
}
