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

        if ($config instanceof ContainerServiceInterface) {
            self::$container = $config->containers(self::$container);
        }

        $event = new EventService();
        if ($config instanceof EventServiceInterface) {
            $event = $config->events($event);
        }

        self::$container->register($event);

        if ($config instanceof ExtensionServiceInterface) {
            $extension = new ExtensionService($this);
            $config->extensions($extension);
        }
    }

    public static function getContainerService(): ContainerService
    {
        return self::$container;
    }

    public function getEventService(): EventService
    {
        return self::getContainerService()->resolve(EventService::class);
    }

    abstract public function initalize(): void;
}
