<?php

declare(strict_types=1);

namespace Darken\Service;

class ContainerService
{
    private static array $containers = [];

    public function register(string|object $name, object|null $container = null): self
    {
        if (is_object($name)) {
            $class = get_class($name);
            $object = $name;
        } else {
            $class = $name;
            $object = $container;
        }

        self::$containers[$class] = $object;
        return $this;
    }

    public function resolve(string $name): ?object
    {
        return self::$containers[$name] ?? null;
    }
}
