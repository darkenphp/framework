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
     * @var array<string, int> The registered containers indexed by their class names or identifiers.
     */
    private array $containers = [];

    /**
     * @var array<int, object|array|callable> The resolved objects indexed by their container index.
     */
    private array $objects = [];

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
     *$container->register(FooBar::class));
     * $container->register(FooBarInterface::class, new FooBar());
     * $container->register(FooBarInterface::class, ['param1' => 'value1', 'param2' => 'value2']);
     * $container->register(FooBarInterface::class, fn() => new FooBar());
     * ```
     *
     * Its also possible to alias multiple containers to the same object:
     *
     * ```php
     * $container->register([FooBarInterface::class, FooBar::class], new FooBar());
     * ```
     *
     * Now you can either resolve the container by its interface or class name.
     */
    public function register(string|array $name, object|array|callable|null $definition = null, bool $system = false): self
    {
        $index = count($this->containers) + 1;

        foreach ((array) $name as $containerName) {
            $this->containers[$containerName] = $index;

            if ($system) {
                $this->systemContainers[] = $containerName;
            }
        }

        $this->objects[$index] = $definition;

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
            // if $name is an instantiable class, then try to create it
            if (class_exists($name)) {
                return $this->create($name);
            }
            throw new RuntimeException(sprintf('Container "%s" not found. Register the container before accessing it.', $name));
        }

        $indexFromName = $this->containers[$name];

        // if $resolve is null, it means no objectOrParams are given and the class i registered by ::class
        $resolve = $this->objects[$indexFromName] ?? null;

        if (is_callable($resolve)) {
            $this->objects[$indexFromName] = $resolve();
        }

        if (is_array($resolve)) {
            $this->objects[$indexFromName] = $this->create($name, $resolve);
        }

        if ($resolve === null) {
            $this->objects[$indexFromName] = $this->create($name);
        }

        return $this->objects[$indexFromName];
    }

    /**
     * Ensure input is an object, resolving it if it's a string.
     *
     * 1. If the input is an object, return it.
     * 2. If the input is a string and the container is registered, resolve it.
     * 3. If the input is a string and the container is not registered, create it (but don't register it).
     *
     * @param object|string|array{0:string,1?:array} $name Object, class name, or [class, params]
     * @param string|null $instanceOf Optional interface/class to assert against
     * @throws InvalidArgumentException
     */
    public function ensure(object|string|array $name, ?string $instanceOf = null): object
    {
        if (is_object($name)) {
            $obj = $name;
        } elseif (is_string($name) && $this->has($name)) {
            $obj = $this->resolve($name);
        } elseif (is_array($name) && isset($name[0]) && is_string($name[0])) {
            $obj = $this->create($name[0], $name[1] ?? []);
        } elseif (is_string($name)) {
            $obj = $this->create($name);
        } else {
            throw new InvalidArgumentException('Invalid argument for ensure(): expected object|string|[class, params].');
        }

        if ($instanceOf !== null && !$obj instanceof $instanceOf) {
            throw new InvalidArgumentException(sprintf(
                'Container "%s" must be an instance of "%s".',
                is_string($name) ? $name : get_debug_type($name),
                $instanceOf
            ));
        }
        return $obj;
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
        unset($this->containers[$name], $this->systemContainers[$name]);
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
