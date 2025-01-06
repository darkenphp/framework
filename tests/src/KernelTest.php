<?php

namespace Tests\src;

use Darken\Config\ConfigInterface;
use Darken\Kernel;
use Tests\TestCase;
use Whoops\Run;

class KernelTest extends TestCase
{
    public function testKernel(): void
    {
        $mock = $this->getMockBuilder(Kernel::class)
            ->setConstructorArgs([$this->createConfig()])
            ->getMock();

        $this->assertInstanceOf(Run::class, $mock->whoops);
    }
}