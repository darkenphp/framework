<?php

namespace Tests\src\Service;

use Darken\Enum\MiddlewarePosition;
use Darken\Service\ContainerService;
use Darken\Service\EventService;
use Darken\Service\MiddlewareService;
use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Tests\data\di\AutoWireTestService;
use Tests\data\di\Db;
use Tests\data\di\MiddlewareObject;
use Tests\data\di\TestEvent;
use Tests\data\di\TestEventDispatch;
use Tests\data\di\TestService;
use Tests\TestCase;

class EventServiceTest extends TestCase
{

    public function testRegisterByStringAndResolvedByContainer()
    {
        $container = new ContainerService();
        $container->register(TestService::class);

        $service = new EventService($container);

        $service->on(TestEventDispatch::class, TestEvent::class);

        $x = $service->dispatch(new TestEventDispatch());

        $this->assertSame('Tests\data\di\TestService', $x->buffer);
    }

    public function testReigsterEventButWithHandler()
    {
        $container = new ContainerService();
        $container->register(TestService::class);

        $service = new EventService($container);

        $service->on(TestEventDispatch::class, [TestEvent::class, 'handler']);

        $x = $service->dispatch(new TestEventDispatch());

        $this->assertSame('static', $x->buffer);
    }
}