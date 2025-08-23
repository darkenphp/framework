<?php

namespace Tests\src\Service;

use Darken\Config\ConfigInterface;
use Darken\Service\ContainerService;
use Darken\Service\RouteService;
use Darken\Web\RouteExtractor;
use Psr\Http\Message\ServerRequestInterface;
use Tests\TestCase;

class RouteServiceTest extends TestCase
{
    private ContainerService $container;
    private ConfigInterface $config;
    private RouteService $routeService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container = new ContainerService();
        $this->config = $this->createConfig();
        
        // Create a routes file for testing
        $routesFile = $this->config->getBuildOutputFolder() . DIRECTORY_SEPARATOR . 'routes.php';
        $this->tmpFile($routesFile, $this->getTestRoutesContent());
        
        $this->routeService = new RouteService($this->container, $this->config);
    }

    public function testRouteServiceInitialization()
    {
        $this->assertInstanceOf(RouteService::class, $this->routeService);
        $this->assertTrue($this->routeService->hasRoutes());
        $this->assertNotEmpty($this->routeService->getRoutes());
    }

    public function testGetRoutes()
    {
        $routes = $this->routeService->getRoutes();
        
        $this->assertIsArray($routes);
        $this->assertArrayHasKey('blogs', $routes);
        $this->assertArrayHasKey('index', $routes);
    }

    public function testCreateRoute()
    {
        $route = $this->routeService->createRoute(
            '/test', 
            'TestClass', 
            ['GET', 'POST'], 
            ['middleware1', 'middleware2']
        );

        $this->assertIsArray($route);
        $this->assertEquals('TestClass', $route['class']);
        $this->assertArrayHasKey('methods', $route);
        $this->assertArrayHasKey('middlewares', $route);
        $this->assertEquals(['middleware1', 'middleware2'], $route['middlewares']);
    }

    public function testCreateRouteWithDefaults()
    {
        $route = $this->routeService->createRoute('/test', 'TestClass');

        $this->assertIsArray($route);
        $this->assertEquals('TestClass', $route['class']);
        $this->assertArrayHasKey('methods', $route);
        $this->assertArrayNotHasKey('middlewares', $route);
    }

    public function testFindRoute()
    {
        $result = $this->routeService->findRoute('/blogs');
        $this->assertIsArray($result);

        $result = $this->routeService->findRoute('/');
        $this->assertIsArray($result);

        $result = $this->routeService->findRoute('/nonexistent');
        $this->assertFalse($result);
    }

    public function testCreateRouteExtractor()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $app = new \stdClass();
        
        $extractor = $this->routeService->createRouteExtractor($app, $request);
        
        $this->assertInstanceOf(RouteExtractor::class, $extractor);
    }

    public function testGetRoutesFile()
    {
        $expectedPath = $this->config->getBuildOutputFolder() . DIRECTORY_SEPARATOR . 'routes.php';
        $this->assertEquals($expectedPath, $this->routeService->getRoutesFile());
    }

    public function testHasRoutesWithEmptyFile()
    {
        // Create empty routes file
        $routesFile = $this->config->getBuildOutputFolder() . DIRECTORY_SEPARATOR . 'routes.php';
        $this->tmpFile($routesFile, '<?php return [];');
        
        $emptyRouteService = new RouteService($this->container, $this->config);
        $this->assertFalse($emptyRouteService->hasRoutes());
    }

    public function testCreateNestedRoutes()
    {
        $routes = [
            [
                'path' => '/api/users',
                'class' => 'ApiUsersController',
                'methods' => ['GET', 'POST'],
                'middlewares' => ['auth'],
            ],
            [
                'path' => '/api/users/<id:[0-9]+>',
                'class' => 'ApiUserController',
                'methods' => ['GET', 'PUT', 'DELETE'],
            ],
            [
                'path' => '/',
                'class' => 'HomeController',
            ],
        ];

        $nestedRoutes = $this->routeService->createNestedRoutes($routes);

        $this->assertIsArray($nestedRoutes);
        $this->assertArrayHasKey('api', $nestedRoutes);
        $this->assertArrayHasKey('index', $nestedRoutes);
        
        // Check nested structure for API routes
        $this->assertArrayHasKey('_children', $nestedRoutes['api']);
        $this->assertArrayHasKey('users', $nestedRoutes['api']['_children']);
        
        // Check methods are properly set
        $this->assertArrayHasKey('methods', $nestedRoutes['api']['_children']['users']['_children']);
        $apiUsersMethods = $nestedRoutes['api']['_children']['users']['_children']['methods'];
        $this->assertArrayHasKey('GET', $apiUsersMethods);
        $this->assertArrayHasKey('POST', $apiUsersMethods);
        $this->assertEquals('ApiUsersController', $apiUsersMethods['GET']['class']);
        $this->assertEquals(['auth'], $apiUsersMethods['GET']['middlewares']);
    }

    public function testGetFlatRoutes()
    {
        $flatRoutes = $this->routeService->getFlatRoutes();
        
        $this->assertIsArray($flatRoutes);
        $this->assertNotEmpty($flatRoutes);
        
        // Find the blogs route
        $blogsRoute = null;
        foreach ($flatRoutes as $route) {
            if ($route['path'] === '/blogs') {
                $blogsRoute = $route;
                break;
            }
        }
        
        $this->assertNotNull($blogsRoute);
        $this->assertEquals('Build\\pages\\blogs\\index', $blogsRoute['class']);
        $this->assertContains('*', $blogsRoute['methods']);
    }

    private function getTestRoutesContent(): string
    {
        return '<?php
return array (
  \'blogs\' => 
  array (
    \'_children\' => 
    array (
      \'<slug:[a-zA-Z0-9\\\\-]+>\' => 
      array (
        \'_children\' => 
        array (
          \'api\' => 
          array (
            \'_children\' => 
            array (
              \'methods\' => 
              array (
                \'*\' => 
                array (
                  \'class\' => \'Build\\\\pages\\\\blogs\\\\slug\\\\api\',
                ),
              ),
            ),
          ),
          \'index\' => 
          array (
            \'_children\' => 
            array (
              \'methods\' => 
              array (
                \'*\' => 
                array (
                  \'class\' => \'Build\\\\pages\\\\blogs\\\\slug\\\\index\',
                ),
              ),
            ),
          ),
        ),
      ),
      \'index\' => 
      array (
        \'_children\' => 
        array (
          \'methods\' => 
          array (
            \'*\' => 
            array (
              \'class\' => \'Build\\\\pages\\\\blogs\\\\index\',
            ),
          ),
        ),
      ),
    ),
  ),
  \'index\' => 
  array (
    \'_children\' => 
    array (
      \'methods\' => 
      array (
        \'*\' => 
        array (
          \'class\' => \'Build\\\\pages\\\\index\',
        ),
      ),
    ),
  ),
);';
    }
}