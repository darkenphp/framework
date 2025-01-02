<?php

declare(strict_types=1);

namespace Darken\Service;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
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

    private array $systemContainers = [];

    public function definitions(bool $system = false): array
    {
        $names = array_keys($this->containers);
        // if system is false, we remove all system containers from the list
        if (!$system) {
            $names = array_diff($names, $this->systemContainers);
        }

        return $names;
    }

    /**
     * Registers a container by name or object.
     *
     * Usage examples:
     *
     * ```php
     * $container->register(new FooBar());
     * $container->register(FooBar::class);
     * $container->register(FooBarInterface::class, new FooBar());
     * $container->register(FooBarInterface::class, ['param1' => 'value1', 'param2' => 'value2']);
     * $container->register(FooBarInterface::class, fn() => new FooBar());
     * ```
     */
    public function register(string|object|callable $objectOrName, object|array|null $objectOrParams = null, bool $system = false): self
    {
        if (is_object($objectOrName)) {
            $class = get_class($objectOrName);
            $object = $objectOrName;
        } else {
            $class = $objectOrName;
            $object = $objectOrParams;
        }

        $this->containers[$class] = $object;

        if ($system) {
            $this->systemContainers[] = $class;
        }

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
        if (!$this->has($name)) {
            throw new RuntimeException(sprintf('Container "%s" not found. Register the container before accessing it.', $name));
        }

        // if $resolve is null, it means no objectOrParams are given and the class i registered by ::class
        $resolve = $this->containers[$name] ?? null;

        if (is_callable($resolve)) {
            $this->containers[$name] = $resolve();
        }

        if (is_array($resolve)) {
            $this->containers[$name] = $this->create($name, $resolve);
        }

        if ($resolve === null) {
            $this->containers[$name] = $this->create($name);
        }

        return $this->containers[$name];
    }

    /**
     * Ensure input is an object, resolving it if it's a string.
     *
     * 1. If the input is an object, return it.
     * 2. If the input is a string and the container is registered, resolve it.
     * 3. If the input is a string and the container is not registered, create it (but don't register it).
     */
    public function ensure(object|string|array $name, string|null $instanceOf = null): object
    {
        if (is_object($name)) {
            $container = $name;
        } elseif (is_string($name) && $this->has($name)) {
            $container = $this->resolve($name);
        } elseif (is_array($name)) {
            $container = $this->create($name[0], $name[1] ?? []);
        } else {
            $container = $this->create($name);
        }

        if ($instanceOf && !$container instanceof $instanceOf) {
            throw new InvalidArgumentException(sprintf(
                'Container "%s" must be an instance of "%s".',
                $name,
                $instanceOf
            ));
        }
        return $container;
    }

    /**
     * Checks if a container is registered.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->containers);
    }

    /**
     * Removes a container from the registry.
     */
    public function remove(string $name): self
    {
        unset($this->containers[$name]);
        return $this;
    }

    /**
     * Creates an object of the specified class with the provided parameters.
     *
     * This method uses reflection to handle classes with constructors that require parameters.
     *
     * @throws InvalidArgumentException If required parameters are missing.
     */
    public function create(string $className, array $params = []): object
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        // If there's no constructor, just return a new instance.
        if (!$constructor) {
            return $reflection->newInstance();
        }

        $args = [];

        foreach ($constructor->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $paramType = $parameter->getType();

            // If the user explicitly provided the parameter in `$params`, just use it.
            if (array_key_exists($paramName, $params)) {
                $args[] = $params[$paramName];
                continue;
            }

            // If the parameter has a type, try to resolve it from the container.
            if ($paramType instanceof ReflectionNamedType && !$paramType->isBuiltin()) {
                $depClass = $paramType->getName();

                // If the dependency is in the container, resolve it.
                if ($this->has($depClass)) {
                    $args[] = $this->resolve($depClass);
                }
                // Otherwise, see if there’s a default value to use.
                elseif ($parameter->isOptional()) {
                    $args[] = $parameter->getDefaultValue();
                }
                // If we can’t resolve a non-optional class dependency, we must throw.
                else {
                    throw new InvalidArgumentException(sprintf(
                        'Unable to resolve parameter "%s" in "%s". ' .
                        'No matching container for "%s" entry or default value.',
                        $paramName,
                        $className,
                        $depClass
                    ));
                }
            } else {
                // For built-in type or untyped parameters, fall back to default if optional.
                if ($parameter->isOptional()) {
                    $args[] = $parameter->getDefaultValue();
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'Missing required parameter "%s" for "%s".',
                        $paramName,
                        $className
                    ));
                }
            }
        }

        // Finally, instantiate the class with the resolved dependencies.
        return $reflection->newInstanceArgs($args);
    }
}
