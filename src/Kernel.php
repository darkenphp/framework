<?php

declare(strict_types=1);

namespace Darken;

use Darken\Repository\Config;

error_reporting(E_ALL);
ini_set('display_errors', '1');

// register error handler
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

class Kernel
{
    private static array $containers = [];

    public function __construct(public readonly Config $config)
    {
        $this->addContainer($config::class, $config);
    }

    public function addContainer(string $name, object $container): void
    {
        self::$containers[$name] = $container;
    }

    public static function getContainer(string $name): ?object
    {
        return self::$containers[$name] ?? null;
    }
}
