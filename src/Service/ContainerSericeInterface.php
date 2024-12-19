<?php

declare(strict_types=1);

namespace Darken\Service;

/**
 * Interface for managing container services in the application.
 * 
 * This interface defines a method for configuring and registering dependency injection (DI) containers
 * using the provided `ContainerService`. It enables the registration of services or objects that can
 * later be injected into other components.
 * 
 * Example usage for registering a service:
 * ```php
 * public function containers(ContainerService $service): ContainerService
 * {
 *     return $service->register(new Test('foo bar'));
 * }
 * ```
 * 
 * Example usage for injecting a registered service:
 * ```php
 * #[\Darken\Attributes\Inject]
 * public Test $test;
 * ```
 */
interface ContainerServiceInterface
{
    /**
     * Configure and register containers for dependency injection.
     * 
     * This method allows the registration of services or objects into the DI container, making them
     * available for injection into other components via attributes or constructor injection.
     * 
     * Example:
     * ```php
     * return $service->register(new Test($this->env('DARKEN_TEST', 'test')));
     * ```
     */
    public function containers(ContainerService $service): ContainerService;
}
