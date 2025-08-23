<?php

declare(strict_types=1);

namespace Darken\Service;

/**
 * Interface for managing route services in the application.
 *
 * This interface defines methods for configuring route services and
 * managing route definitions. It enables the creation and customization
 * of routes for enhanced request routing.
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