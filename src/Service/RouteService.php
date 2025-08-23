<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Web\RouteExtractor;
use Darken\Config\ConfigInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Route Service for managing application routes and route extraction.
 *
 * This service provides methods to create routes, resolve route extractors,
 * and manage route definitions loaded from the compiled routes.php file.
 */
final class RouteService
{
    private array $routes = [];
    private string $routesFile = '';

    public function __construct(private ContainerService $containerService, private ConfigInterface $config)
    {
        $this->routesFile = $this->config->getBuildOutputFolder() . DIRECTORY_SEPARATOR . 'routes.php';
        $this->loadRoutes();
    }

    /**
     * Load routes from the compiled routes.php file.
     */
    private function loadRoutes(): void
    {
        if (file_exists($this->routesFile) && is_readable($this->routesFile)) {
            $this->routes = include($this->routesFile);
        }
    }

    /**
     * Get all loaded routes.
     *
     * @return array The routes array structure
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Create a RouteExtractor instance for the given request.
     *
     * @param object $app The application instance
     * @param ServerRequestInterface $request The server request
     * @return RouteExtractor The route extractor instance
     */
    public function createRouteExtractor(object $app, ServerRequestInterface $request): RouteExtractor
    {
        return new RouteExtractor($app, $request);
    }

    /**
     * Create a route definition based on provided options.
     *
     * @param string $path The route path
     * @param string $class The handler class
     * @param array $methods The HTTP methods (default: ['*'])
     * @param array $middlewares The middlewares for this route (default: [])
     * @return array The route definition
     */
    public function createRoute(string $path, string $class, array $methods = ['*'], array $middlewares = []): array
    {
        $routeDefinition = [
            'class' => $class,
            'methods' => [],
        ];

        if (!empty($middlewares)) {
            $routeDefinition['middlewares'] = $middlewares;
        }

        foreach ($methods as $method) {
            $routeDefinition['methods'][$method] = $routeDefinition;
        }

        return $routeDefinition;
    }

    /**
     * Find a route in the routes structure by path.
     *
     * @param string $path The path to search for
     * @return array|false The route definition or false if not found
     */
    public function findRoute(string $path): array|false
    {
        $segments = explode('/', trim($path, '/'));
        
        // Handle root route
        if (count($segments) === 1 && empty($segments[0])) {
            $segments = ['index'];
        } else {
            $segments[] = 'index';
        }

        $node = $this->routes;
        $params = [];

        foreach ($segments as $segment) {
            if (isset($node[$segment])) {
                $node = $node[$segment]['_children'] ?? [];
                continue;
            }

            // Try dynamic routes
            $found = false;
            foreach ($node as $key => $child) {
                if (strpos($key, '<') === 0 && strpos($key, '>') === strlen($key) - 1) {
                    $pattern = substr($key, 1, -1);
                    [$name, $regex] = explode(':', $pattern);
                    
                    if (preg_match("/^$regex$/", $segment)) {
                        $params[$name] = $segment;
                        $node = $child['_children'] ?? [];
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) {
                return false;
            }
        }

        if (isset($node['methods'])) {
            return [$node, $params];
        }

        return false;
    }

    /**
     * Check if routes are available (routes.php file exists and is readable).
     *
     * @return bool True if routes are available
     */
    public function hasRoutes(): bool
    {
        return !empty($this->routes);
    }

    /**
     * Get the routes file path.
     *
     * @return string The routes file path
     */
    public function getRoutesFile(): string
    {
        return $this->routesFile;
    }
}