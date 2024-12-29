<?php

declare(strict_types=1);

namespace Darken;

use Darken\Config\ConfigInterface;
use Darken\Service\ContainerService;
use Darken\Service\ContainerServiceInterface;
use Darken\Service\EventService;
use Darken\Service\EventServiceInterface;
use Whoops\Run;

abstract class Kernel
{
    public Run $whoops;

    private static ContainerService $container;

    public function __construct(public readonly ConfigInterface $config)
    {
        $this->whoops = new Run();
        $this->initalize();

        $container = new ContainerService();
        $container->register($config::class, $config);

        if ($config instanceof ContainerServiceInterface) {
            $container = $config->containers($container);
        }

        $event = new EventService();
        if ($config instanceof EventServiceInterface) {
            $event = $config->events($event);
        }

        $container->register($event);

        self::$container = $container;
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
