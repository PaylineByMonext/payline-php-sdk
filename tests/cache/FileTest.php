<?php

namespace Cache;

use Payline\Cache\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{

    public function testIsAvailable()
    {
        $mockFile = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->assertTrue($mockFile->isAvailable());
    }

    public function testLoadServicesEndpoints()
    {
        $mockFile = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        // Test
        $loadServicesEndpoints = $mockFile->loadServicesEndpoints();

        // Verif
        $this->assertNotNull($loadServicesEndpoints);
        $this->assertFalse($loadServicesEndpoints);
    }

    public function testSaveServicesEndpoints()
    {
        $file = new File();
        $file->clear();

        // Test
        $result = $file->saveServicesEndpoints(['http://endpoint1.fr', 'http://endpoint2.fr'], 200);
        $result = $file->saveServicesEndpoints(['http://endpoint1.fr', 'http://endpoint2.fr'], 200);

        // Verif
        $this->assertNotNull($result);
        $this->assertTrue($result);

        $endpoints = $file->loadServicesEndpoints();
        $this->assertNotNull($endpoints);
        $this->assertEquals('http://endpoint1.fr', $endpoints[0]);
        $this->assertEquals('http://endpoint2.fr', $endpoints[1]);
    }

    public function testHasServicesEndpoints()
    {
        $file = new File();
        $file->clear();

        // Test
        $loadServicesEndpoints = $file->hasServicesEndpoints();

        // Verif
        $this->assertNotNull($loadServicesEndpoints);
        $this->assertFalse($loadServicesEndpoints);
    }
}
