<?php

namespace Tests\src\Service;

use Darken\Enum\MiddlewarePosition;
use Darken\Service\ContainerService;
use Darken\Service\MiddlewareService;
use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Tests\data\di\AutoWireTestService;
use Tests\data\di\Db;
use Tests\data\di\MiddlewareObject;
use Tests\data\di\TestService;
use Tests\TestCase;

class MiddlewareServiceTest extends TestCase
{

    public function testRegisterByStringAndResolvedByContainer()
    {
        $container = new ContainerService();

        $service = new MiddlewareService($container);

        $service->register(MiddlewareObject::class, MiddlewarePosition::AFTER);

        $this->assertInstanceOf(MiddlewareObject::class, $service->getChain()[0]);
    }
}