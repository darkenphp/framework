<?php

declare(strict_types=1);

namespace Darken\Service;

use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;

/**
 * A lightweight service container for managing dependencies.
 *
 * This class allows for registering and resolving services or objects, enabling
 * dependency injection and improved application modularity.
 */
final class ContainerService
{
    /**
     * @var array<string, object|array|callable> The registered containers indexed by their class names or identifiers.
     */
    private array $containers = [];

    /**
     * Registers a container by name or object.
     *
     * Usage examples:
     *
     * ```php
     * $container->register(new FooBar());
     * $container->register(FooBarInterface::class, new FooBar());
     * $container->register(FooBarInterface::class, ['param1' => 'value1', 'param2' => 'value2']);
     * $container->register(FooBarInterface::class, fn() => new FooBar());
     * ```
     */
    public function register(string|object|callable $name, object|array|null $container = null): self
    {
        if (is_object($name)) {
            $class = get_class($name);
            $object = $name;
        } else {
            $class = $name;
            $object = $container;
        }

        $this->containers[$class] = $object;
        return $this;
    }

    /**
     * Resolves a container by its name.
     *
     * If the container is callable, it will be executed and replaced with its result.
     * If it is an array, it will be used to instantiate an object of the specified class.
     *
     * @throws RuntimeException If the container is not found.
     */
    public function resolve(string $name): object
    {
        $resolve = $this->containers[$name] ?? null;

        if (!$resolve) {
            throw new RuntimeException(sprintf('Container "%s" not found. Register the container before accessing it.', $name));
        }

        if (is_callable($resolve)) {
            $this->containers[$name] = $resolve();
        }

        if (is_array($resolve)) {
            $this->containers[$name] = $this->createObject($name, $resolve);
        }

        return $this->containers[$name];
    }

    /**
     * Creates an object of the specified class with the provided parameters.
     *
     * This method uses reflection to handle classes with constructors that require parameters.
     *
     * @throws InvalidArgumentException If required parameters are missing.
     */
    public function createObject(string $className, array $params = []): object
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor) {
            $requiredParams = $constructor->getNumberOfRequiredParameters();
            if (count($params) < $requiredParams) {
                throw new InvalidArgumentException('Missing required parameters for constructor.');
            }

            return $reflection->newInstanceArgs($params);
        }

        return $reflection->newInstance();
    }

    public function remove(string $name): self
    {
        unset($this->containers[$name]);
        return $this;
    }
}
