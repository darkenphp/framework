<?php

declare(strict_types=1);

namespace Darken\Service;

use InvalidArgumentException;
use ReflectionClass;

class ContainerService
{
    private static array $containers = [];

    /**
     * ->registr(new FooBar());
     * ->register(FooBarInterface::class, new FooBar());
     * ->register(FooBarInterface::class, ['param1' => 'value1', 'param2' => 'value2']);
     * ->register(FooBarInterface::class, function() { return new FooBar(); });
     */
    public function register(string|object|callable $name, object|null|array $container = null): self
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
        $resolve = self::$containers[$name];

        if (is_callable($resolve)) {
            self::$containers[$name] = $resolve();
        }

        if (is_array($resolve)) {
            self::$containers[$name] = $this->createObject($name, $resolve);
        }

        return self::$containers[$name] ?? null;
    }

    /**
     * Safely creates an object of the given class with the provided parameters.
     *
     * @throws InvalidArgumentException If required parameters are missing.
     */
    public function createObject(string $className, array $params): object
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor) {
            $requiredParams = $constructor->getNumberOfRequiredParameters();
            if (count($params) >= $requiredParams) {
                // Create the object if parameters match
                $object = new $className(...$params);
            } else {
                // Handle missing parameters, throw an exception
                throw new InvalidArgumentException('Missing required parameters for constructor.');
            }
        } else {
            // Create the object without any parameters if there's no constructor
            $object = new $className();
        }

        return $object;
    }
}
