<?php

declare(strict_types=1);

namespace Darken;

use Darken\Config\ConfigInterface;
use Darken\Service\ContainerServiceInterface;
use Darken\Service\ContainerService;
use Whoops\Run;

error_reporting(E_ALL);
ini_set('display_errors', '1');

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

        self::$container = $container;
    }

    public static function resolveContainer(string $className): object
    {
        return self::$container->resolve($className);
    }

    abstract public function initalize(): void;
}
