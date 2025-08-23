<?php

declare(strict_types=1);

namespace Darken\Service;

/**
 * Interface for managing route services in the application.
 *
 * This interface defines methods for configuring route services and
 * managing route definitions. It enables the creation and customization
 * of routes for enhanced request routing.
 * 
 * The RouteService provides several key capabilities:
 * 
 * 1. **Route Loading**: Automatically loads routes from the compiled routes.php file
 * 2. **Route Creation**: Create individual routes and nested route structures
 * 3. **Route Extraction**: Factory for RouteExtractor instances used in request processing
 * 4. **Route Introspection**: Methods to find, list, and analyze routes
 * 
 * Implementation example:
 * ```php
 * class AppConfig implements RouteServiceInterface 
 * {
 *     public function routes(RouteService $service): RouteService 
 *     {
 *         // Customize the route service if needed
 *         return $service;
 *     }
 * }
 * ```
 * 
 * The service is automatically registered in the Kernel and can be accessed via:
 * ```php
 * $routeService = $app->getRouteService();
 * ```
 */
interface RouteServiceInterface
{
    /**
     * Configure and manage routes for the application.
     *
     * This method allows the registration of custom routes to the service
     * for processing incoming requests and mapping them to appropriate handlers.
     *
     * @param RouteService $service The route service instance to configure
     * @return RouteService The configured route service
     */
    public function routes(RouteService $service): RouteService;
}