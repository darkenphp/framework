<?php

namespace Tests\src\Service;

use Darken\Service\ContainerService;
use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Tests\data\di\AutoWireTestService;
use Tests\data\di\Db;
use Tests\data\di\TestService;
use Tests\TestCase;

class ContainerServiceTest extends TestCase
{
    public function testMissingResolver()
    {
        $service = new ContainerService();
        $this->expectException(RuntimeException::class);
        $service->resolve('FooBar');
    }

    public function testRegisterCallable()
    {
        $service = new ContainerService();
        $service->register('FooBar', function() {
            return new stdClass();
        });

        $this->assertInstanceOf(stdClass::class, $service->resolve('FooBar'));
    }

    public function testArrayAnnotationRegister()
    {
        $service = new ContainerService();
        $service->register(stdClass::class, ['param1' => 'value1', 'param2' => 'value2']);

        $this->assertInstanceOf(stdClass::class, $service->resolve(stdClass::class));
    }

    public function testCreateObjectWithAMissingConstrucotr()
    {
        $service = new ContainerService();
        $this->expectException(InvalidArgumentException::class);
        $service->create(Db::class);
    }

    public function testCreateObjectWithoutParams()
    {
        $service = new ContainerService();
        $object = $service->create(TestService::class);
        $this->assertInstanceOf(TestService::class, $object);
    }

    public function testAutoWirte()
    {
        $service = new ContainerService();
        
        $service->register(TestService::class, new TestService());

        $object = $service->create(AutoWireTestService::class);
        
        $this->assertInstanceOf(AutoWireTestService::class, $object);

        $this->assertSame('Tests\data\di\TestService', $object->testServiceName());
    }

    public function testEnsure()
    {
        $service = new ContainerService();
        
        $this->assertInstanceOf(TestService::class, $service->ensure(TestService::class));
        $this->assertInstanceOf(TestService::class, $service->ensure(new TestService()));
        $this->assertInstanceOf(TestService::class, $service->ensure([TestService::class]));

        $service->register(AutoWireTestService::class);
        // since AutoWireTestService needs to inject TestService, it needs to be registered first.
        $service->register(TestService::class);

        $this->assertInstanceOf(AutoWireTestService::class, $service->ensure(AutoWireTestService::class));
    }

    public function testEnsureInstanceOf()
    {
        $service = new ContainerService();
        
        $this->assertInstanceOf(TestService::class, $service->ensure(TestService::class, TestService::class));
        $this->assertInstanceOf(TestService::class, $service->ensure(new TestService(), TestService::class));
        $this->assertInstanceOf(TestService::class, $service->ensure([TestService::class], TestService::class));

        $this->expectException(InvalidArgumentException::class);
        $service->ensure(AutoWireTestService::class, TestService::class);
    }
}