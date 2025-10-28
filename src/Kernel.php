<?php

declare(strict_types=1);

namespace Darken;

use Darken\Config\ConfigInterface;
use Darken\Events\AppInitializeEvent;
use Darken\Events\AppShutdownEvent;
use Darken\Service\ContainerService;
use Darken\Service\ContainerServiceInterface;
use Darken\Service\EventService;
use Darken\Service\EventServiceInterface;
use Darken\Service\ExtensionService;
use Darken\Service\ExtensionServiceInterface;
use Darken\Service\LogService;
use Darken\Service\MiddlewareService;
use Darken\Service\MiddlewareServiceInterface;
use Darken\Service\RouteService;
use Throwable;
use Whoops\Handler\CallbackHandler;
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
        $this->whoops->allowQuit(false);
        $this->whoops->appendHandler(new CallbackHandler(function (Throwable $exception) {
            $this->getLogService()->error('Uncaught exception: {message}', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
                'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'stack_trace' => $exception->getTraceAsString(),
            ]);
        }));
        $this->initalize();

        self::$container = new ContainerService();
        self::$container->register([$config::class, ConfigInterface::class], $config, true);

        // register containers from config
        if ($config instanceof ContainerServiceInterface) {
            self::$container = $config->containers(self::$container);
        }

        // event service
        $event = new EventService(self::$container);
        if ($config instanceof EventServiceInterface) {
            $event = $config->events($event);
        }
        $event->dispatch(new AppInitializeEvent($this));

        // middleware service
        $middlewareService = new MiddlewareService(self::$container);
        if ($this->config instanceof MiddlewareServiceInterface) {
            $middlewareService = $this->config->middlewares($middlewareService);
        }
        self::$container->register($middlewareService::class, $middlewareService, true);

        self::$container->register($event::class, $event, true);
        self::$container->register(LogService::class, null, true);
        self::$container->register(RouteService::class, null, true);

        // extension service
        $extension = new ExtensionService($this);
        if ($config instanceof ExtensionServiceInterface) {
            $extension = $config->extensions($extension);
        }
        self::$container->register($extension::class, $extension, true);

        register_shutdown_function([$this, 'handleShutdown']);
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

    public function getRouteService(): RouteService
    {
        return self::getContainerService()->resolve(RouteService::class);
    }

    public function getLogService(): LogService
    {
        return self::getContainerService()->resolve(LogService::class);
    }

    public function getExtensionService(): ExtensionService
    {
        return self::getContainerService()->resolve(ExtensionService::class);
    }

    public function handleShutdown(): void
    {
        $this->getEventService()->dispatch(new AppShutdownEvent($this));
    }

    abstract public function initalize(): void;
}
