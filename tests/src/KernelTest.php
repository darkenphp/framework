<?php

namespace Tests\src;

use Darken\Kernel;
use Tests\TestCase;

class KernelTest extends TestCase
{
    public function testKernel(): void
    {
        $mock = $this->getMockBuilder(Kernel::class)
            ->setConstructorArgs([$this->createConfig()])
            ->getMock();

        
    }
}