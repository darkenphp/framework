<?php

declare(strict_types=1);

namespace Darken\Service;

class ContainerService
{
    private static array $containers = [];

    public function register(string $name, object $container): self
    {
        self::$containers[$name] = $container;
        return $this;
    }

    public function resolve(string $name): ?object
    {
        return self::$containers[$name] ?? null;
    }
}
