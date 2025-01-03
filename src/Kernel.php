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

/**
 * Kernel
 *
 * This class is the base class for the application. It provides the necessary
 * methods to initalize the application and register the services.
 */
abstract class Kernel
{
    public Run $whoops;

    private static ContainerService $container;

    public function __construct(public readonly ConfigInterface $config)
    {
        $this->whoops = new Run();
        $this->initalize();

        self::$container = new ContainerService();
        self::$container->register($config::class, $config, true);

        // register containers from config
        if ($config instanceof ContainerServiceInterface) {
            self::$container = $config->containers(self::$container);
        }

        // middleware service
        $middlewareService = new MiddlewareService(self::$container);
        if ($this->config instanceof MiddlewareServiceInterface) {
            $middlewareService = $this->config->middlewares($middlewareService);
        }
        self::$container->register($middlewareService, null, true);

        // event service
        $event = new EventService(self::$container);
        if ($config instanceof EventServiceInterface) {
            $event = $config->events($event);
        }
        self::$container->register($event, null, true);

        // extension service
        $extension = new ExtensionService($this);
        if ($config instanceof ExtensionServiceInterface) {
            $extension = $config->extensions($extension);
        }
        self::$container->register($extension, null, true);
    }

    public static function getContainerService(): ContainerService
    {
        return self::$container;
    }

    public function getEventService(): EventService
    {
        return self::getContainerService()->resolve(EventService::class);
    }

    public function getMiddlewareService(): MiddlewareService
    {
        return self::getContainerService()->resolve(MiddlewareService::class);
    }

    public function getExtensionService(): ExtensionService
    {
        return self::getContainerService()->resolve(ExtensionService::class);
    }

    abstract public function initalize(): void;
}
