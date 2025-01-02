<?php

declare(strict_types=1);

namespace Darken;

use Darken\Config\ConfigInterface;
use Darken\Service\ContainerService;
use Darken\Service\ContainerServiceInterface;
use Darken\Service\EventService;
use Darken\Service\EventServiceInterface;
use Darken\Service\ExtensionService;
use Darken\Service\ExtensionServiceInterface;
use Darken\Service\MiddlewareService;
use Darken\Service\MiddlewareServiceInterface;
use Whoops\Run;

abstract class Kernel
{
    public Run $whoops;

    private static ContainerService $container;

    public function __construct(public readonly ConfigInterface $config)
    {
        $this->whoops = new Run();
        $this->initalize();

        self::$container = new ContainerService();
        self::$container->register($config::class, $config);

        // register containers from config
        if ($config instanceof ContainerServiceInterface) {
            self::$container = $config->containers(self::$container);
        }

        // middleware service
        $middlewareService = new MiddlewareService(self::$container);
        if ($this->config instanceof MiddlewareServiceInterface) {
            $middlewareService = $this->config->middlewares($middlewareService);
        }
        self::$container->register($middlewareService);

        // event service
        $event = new EventService();
        if ($config instanceof EventServiceInterface) {
            $event = $config->events($event);
        }
        self::$container->register($event);

        // extension service
        $extension = new ExtensionService($this);
        if ($config instanceof ExtensionServiceInterface) {
            $extension = $config->extensions($extension);
        }
        self::$container->register($extension);
    }

    public static function getContainerService(): ContainerService
    {
        return self::$container;
    }

    public function getEventService(): EventService
    {
        return self::getContainerService()->resolve(EventService::class);
    }

    public function getMiddlwareService(): MiddlewareService
    {
        return self::getContainerService()->resolve(MiddlewareService::class);
    }

    public function getExtensionService(): ExtensionService
    {
        return self::getContainerService()->resolve(ExtensionService::class);
    }

    abstract public function initalize(): void;
}
