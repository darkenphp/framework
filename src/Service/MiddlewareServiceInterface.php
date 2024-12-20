<?php

declare(strict_types=1);

namespace Darken\Service;

/**
 * Interface for managing middleware services in the application.
 *
 * This interface defines a method for configuring middleware services using the
 * provided `MiddlewareService`. It enables the addition and customization of
 * middleware for enhanced request handling.
 *
 * Example usage for configuring middleware:
 * ```php
 * public function middlewares(MiddlewareService $service): MiddlewareService
 * {
 *     return $service->add(new AddCustomHeaderMiddleware('Authorization', 'test'), MiddlewarePosition::BEFORE);
 * }
 * ```
 *
 * Example usage for setting up middleware on a page:
 * ```php
 * $page = new
 *     #[\Darken\Attributes\Middleware(AddCustomHeaderMiddleware::class, ['name' => 'Key', 'value' => 'Bar'], MiddlewarePosition::BEFORE)]
 *     class {
 *         // Page-specific implementation
 *     };
 * ```
 */
interface MiddlewareServiceInterface
{
    /**
     * Configure and manage middleware for the application.
     *
     * This method allows the addition of custom middleware to the service pipeline
     * for processing incoming requests and responses. Developers can specify the
     * middleware's position in the pipeline.
     *
     * Example:
     * ```php
     * return $service->add(new AddCustomHeaderMiddleware('Authorization', 'test'), MiddlewarePosition::BEFORE);
     * ```
     */
    public function middlewares(MiddlewareService $service): MiddlewareService;
}
