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
 * 
 * Key features:
 * - Load and manage routes from the compiled routes.php file
 * - Create RouteExtractor instances for request processing
 * - Create route definitions with support for HTTP methods and middlewares
 * - Build nested route structures compatible with the framework's format
 * - Extract flat route listings for debugging and introspection
 * - Route finding and matching utilities
 * 
 * Example usage:
 * ```php
 * // Get the service from Kernel
 * $routeService = $app->getRouteService();
 * 
 * // Create a simple route
 * $route = $routeService->createRoute('/api/users', 'UserController', ['GET', 'POST'], ['auth']);
 * 
 * // Create nested routes structure
 * $routes = [
 *     ['path' => '/api/users', 'class' => 'UserController', 'methods' => ['GET', 'POST']],
 *     ['path' => '/api/posts', 'class' => 'PostController'],
 * ];
 * $nestedRoutes = $routeService->createNestedRoutes($routes);
 * 
 * // Get all routes as flat array
 * $allRoutes = $routeService->getFlatRoutes();
 * 
 * // Find a specific route
 * $found = $routeService->findRoute('/api/users');
 * ```
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
     * Create a nested route structure suitable for routes.php format.
     *
     * @param array $routes Array of route definitions with 'path', 'class', 'methods', and optional 'middlewares'
     * @return array The nested route structure
     */
    public function createNestedRoutes(array $routes): array
    {
        $nestedRoutes = [];

        foreach ($routes as $route) {
            $path = trim($route['path'], '/');
            $segments = $path ? explode('/', $path) : [];
            $segments[] = 'index'; // Add index for consistency with the framework pattern

            $current = &$nestedRoutes;
            foreach ($segments as $i => $segment) {
                if ($i === count($segments) - 1) {
                    // Last segment - add the methods
                    $current[$segment]['_children']['methods'] = [];
                    $methods = $route['methods'] ?? ['*'];
                    
                    $routeConfig = ['class' => $route['class']];
                    if (!empty($route['middlewares'])) {
                        $routeConfig['middlewares'] = $route['middlewares'];
                    }

                    foreach ($methods as $method) {
                        $current[$segment]['_children']['methods'][$method] = $routeConfig;
                    }
                } else {
                    // Intermediate segment
                    if (!isset($current[$segment])) {
                        $current[$segment] = ['_children' => []];
                    }
                    $current = &$current[$segment]['_children'];
                }
            }
        }

        return $nestedRoutes;
    }

    /**
     * Get all available routes as a flat array with their paths and classes.
     *
     * @return array Array of routes with 'path', 'class', 'methods', and 'middlewares'
     */
    public function getFlatRoutes(): array
    {
        return $this->flattenRoutes($this->routes);
    }

    /**
     * Recursively flatten the nested route structure.
     *
     * @param array $routes The nested routes structure
     * @param string $basePath The base path for current level
     * @return array Flattened routes
     */
    private function flattenRoutes(array $routes, string $basePath = ''): array
    {
        $flatRoutes = [];

        foreach ($routes as $segment => $data) {
            $currentPath = $basePath ? $basePath . '/' . $segment : $segment;

            if (isset($data['_children']['methods'])) {
                // This is a route endpoint
                $methods = array_keys($data['_children']['methods']);
                $routeData = reset($data['_children']['methods']); // Get first method data

                $route = [
                    'path' => '/' . str_replace('/index', '', $currentPath),
                    'class' => $routeData['class'],
                    'methods' => $methods,
                ];

                if (!empty($routeData['middlewares'])) {
                    $route['middlewares'] = $routeData['middlewares'];
                }

                $flatRoutes[] = $route;
            }

            if (isset($data['_children']) && is_array($data['_children'])) {
                // Recursively process children, but skip 'methods' key
                $children = array_filter($data['_children'], function($key) {
                    return $key !== 'methods';
                }, ARRAY_FILTER_USE_KEY);

                if (!empty($children)) {
                    $flatRoutes = array_merge($flatRoutes, $this->flattenRoutes($children, $currentPath));
                }
            }
        }

        return $flatRoutes;
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