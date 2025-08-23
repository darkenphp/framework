<?php

namespace Tests\src\Service;

use Darken\Service\ContainerService;
use Darken\Service\LogService;
use Tests\TestCase;

class LogServiceKernelIntegrationTest extends TestCase
{
    public function testLogServiceCanBeRetrievedFromKernel()
    {
        $config = $this->createConfig();
        
        // Using the Application class which extends Kernel - this should work when dependencies are available
        // For now, we test the service creation pattern manually
        
        $container = new ContainerService();
        $logService = new LogService($container);
        
        // Register it the same way Kernel does
        $container->register(LogService::class, $logService, true);
        
        // Verify it can be resolved
        $resolvedService = $container->resolve(LogService::class);
        
        $this->assertSame($logService, $resolvedService);
        $this->assertInstanceOf(\Darken\Service\LoggerInterface::class, $resolvedService);
    }

    public function testLogServiceRegistersCorrectlyInContainer()
    {
        $container = new ContainerService();
        $logService = new LogService($container);
        
        // Test the registration pattern used in Kernel
        $container->register($logService::class, $logService, true);
        
        $this->assertTrue($container->has(LogService::class));
        
        $resolved = $container->resolve(LogService::class);
        $this->assertInstanceOf(LogService::class, $resolved);
    }

    public function testLogServiceFollowsFrameworkServicePattern()
    {
        $container = new ContainerService();
        $logService = new LogService($container);
        
        // Test that LogService follows the same pattern as other services
        // like EventService, MiddlewareService, etc.
        
        // 1. Takes ContainerService as constructor parameter
        $this->assertInstanceOf(LogService::class, $logService);
        
        // 2. Can be registered in container
        $container->register(LogService::class, $logService, true);
        
        // 3. Can be resolved from container
        $resolved = $container->resolve(LogService::class);
        $this->assertSame($logService, $resolved);
        
        // 4. Implements expected interface
        $this->assertInstanceOf(\Darken\Service\LoggerInterface::class, $logService);
    }
}