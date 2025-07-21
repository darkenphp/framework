<?php

namespace Tests\src\Console\Commands;

use Darken\Console\Application;
use Darken\Console\Commands\Build;
use Darken\Events\AfterBuildEvent;
use Darken\Service\EventService;
use Darken\Service\ExtensionInterface;
use Darken\Service\ExtensionService;
use Darken\Service\ExtensionServiceInterface;
use Darken\Web\Application as WebApplication;
use Darken\Web\PageHandler;
use Darken\Web\Request;
use Darken\Web\RouteExtractor;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use Tests\data\di\Db;
use Tests\TestCase;
use Tests\TestConfig;
use Yiisoft\Files\FileHelper;

class BuildTest extends TestCase
{
    public function testBuild()
    {
        $config = $this->createConfig();

        FileHelper::ensureDirectory($config->getBuildOutputFolder());

        $this->assertStringContainsString('tests/.build', $config->getBuildOutputFolder());
        $this->assertStringContainsString('tests/data/pages', $config->getPagesFolder());

        $app = new Application($config);

        $app->getEventService()->on(AfterBuildEvent::class, function () {
            $this->assertTrue(true);
        });

        $build = new Build();

        $build->run($app);
        
        $content = include($config->getBuildOutputFolder() . '/routes.php');

        $this->assertSame([
            '<slug:.+>' => [
                '_children' => [
                    'methods' => [
                        '*' => [
                            'class' => 'Tests\Build\data\pages\slug',
                        ]
                    ]
                ],
            ],
            'api' => [
                '_children' => [
                    'auth' => [
                        '_children' => [
                            'methods' => [
                                '*' => [
                                    'middlewares' => [
                                        [
                                            'class' => '\Darken\Middleware\CorsMiddleware',
                                            'params' => [],
                                            'position' => '\Darken\Enum\MiddlewarePosition::BEFORE',
                                        ],
                                    ],
                                    'class' => 'Tests\Build\data\pages\api\auth',
                                ]
                            ]
                        ],
                    ],
                ],
            ],
            'blogs' => [
                '_children' => [
                    '<id:[a-zA-Z0-9\-]+>' => [
                        '_children' => [
                            'comments' => [
                                '_children' => [
                                    'methods' => [
                                        '*' => [
                                            'middlewares' => [
                                                [
                                                    'class' => '\Darken\Middleware\CorsMiddleware',
                                                    'params' => [],
                                                    'position' => '\Darken\Enum\MiddlewarePosition::AFTER',
                                                ],
                                            ],
                                            'class' => 'Tests\Build\data\pages\blogs\id\comments',
                                        ],
                                    ],
                                ],
                            ],
                            'index' => [
                                '_children' => [
                                    'methods' => [
                                        '*' => [
                                            'class' => 'Tests\Build\data\pages\blogs\id\index',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'index' => [
                        '_children' => [
                            'methods' => [
                                '*' => [
                                    'class' => 'Tests\Build\data\pages\blogs\index',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components-test' => [
                '_children' => [
                    'methods' => [
                        '*' => [
                            'class' => 'Tests\Build\data\pages\componentstest',
                        ]
                    ]
                ],
            ],
            'hello' => [
                '_children' => [
                    'methods' => [
                        'GET' => [
                            'methods' => [
                                'GET',
                                'POST',
                            ],
                            'class' => 'Tests\Build\data\pages\hello',
                        ],
                        'POST' => [
                            'methods' => [
                                'GET',
                                'POST',
                            ],
                            'class' => 'Tests\Build\data\pages\hello',
                        ]
                    ]
                ],
            ],
            'nested' => [
                '_children' => [
                    '<level1:[a-zA-Z0-9\-]+>' => [
                        '_children' => [
                            '<level2:[a-zA-Z0-9\-]+>' => [
                                '_children' => [
                                    'methods' => [
                                        'GET' => [
                                            'class' => 'Tests\Build\data\pages\nested\level1\level2getpostput',
                                        ],
                                        'POST' => [
                                            'class' => 'Tests\Build\data\pages\nested\level1\level2getpostput',
                                        ],
                                        'PUT' => [
                                            'class' => 'Tests\Build\data\pages\nested\level1\level2getpostput',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'params' =>  [
                '_children' =>  [
                    'methods' => [
                        '*' => [
                            'middlewares' =>  [
                                0 => [
                                    'class' => '\Darken\Middleware\CorsMiddleware',
                                    'params' => [],
                                    'position' => 'before',
                                ],
                            ],
                            'class' => 'Tests\Build\data\pages\params',
                        ],
                    ],
                ],
            ],
            'users' => [
                '_children' => [
                    '<test:[a-zA-Z0-9\-]+>-methods' => [
                        '_children' => [
                            'methods' => [
                                'GET' => [
                                    'class' => 'Tests\Build\data\pages\users\testmethodsgetpost',
                                ],
                                'POST' => [
                                    'class' => 'Tests\Build\data\pages\users\testmethodsgetpost',
                                ],
                            ],
                        ],
                    ],
                    'index' => [
                        '_children' => [
                            'methods' => [
                                'GET' => [
                                    'class' => 'Tests\Build\data\pages\users\indexget',
                                ],
                                'POST' => [
                                    'class' => 'Tests\Build\data\pages\users\indexpost',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $content);

        // Specific test for method sorting in nested structures
        $this->assertMethodsSortedCorrectly($content);

        $extensionFilePath = $config->getBuildOutputFolder() . '/Extension.php';

        $this->assertTrue(file_exists($extensionFilePath));

        $namespace = 'Tests\Build\Extension';
        $obj = new $namespace(new Db('dsn'));
        $this->assertInstanceOf(ExtensionInterface::class, $obj);

        // web app
        $web = new WebApplication($config);
        $web->whoops->unregister();

        $web->getContainerService()->register(Request::class, $this->createServerRequest('/?query=foobar', 'GET'));

        foreach ([
            'index' => [
                200, 'pages/[[...slug]]:index'
            ],
            '' => [
                200, 'pages/[[...slug]]:'
            ],
            'does/not/exist/but/wildcard' => [
                200, 'pages/[[...slug]]:does/not/exist/but/wildcard'
            ],
            'does/not/with/trailing/' => [
                200, 'pages/[[...slug]]:does/not/with/trailing'
            ],
            'hello' => [
                200, 'pages/hello'
            ],
            'blogs' => [
                200, 'pages/blogs'
            ],
            'blogs/1' => [
                200, 'pages/blogs/[[id]]:1'
            ],
            'blogs/1/comments' => [
                200, 'pages/blogs/[[id]]/comments:1'
            ],
            'api/auth' => [
                200, '{"message":"auth-api"}' // middleware is not extracted and processed in page handler!
            ],
            'params' => [
                200, 'pages/params'
            ],
        ] as $path => $def) {

            $extractor = new RouteExtractor($web, $this->createServerRequest($path, 'GET'));

            $handler = new PageHandler($extractor);
            $response = $handler->handle($this->createServerRequest($path, 'GET'));

            $this->assertInstanceOf(ResponseInterface::class, $response);

            $this->assertSame($def[0], $response->getStatusCode(), "Failed for GET path: $path");
            $this->assertSame($def[1], (string) $response->getBody(), "Failed for GET path: $path");
        }
        
        $reflection = new ReflectionClass($web);
        $method = $reflection->getMethod('handleServerRequest');
        $method->setAccessible(true);



        // test hello with different http methods then GET & POST, should be invalid.

        $helloGetResponse = $method->invoke($web, $this->createServerRequest('hello', 'GET'));
        $this->assertSame('pages/hello', $helloGetResponse->getBody()->getContents());

        $helloPostResponse = $method->invoke($web, $this->createServerRequest('hello', 'POST'));
        $this->assertSame('pages/hello', $helloPostResponse->getBody()->getContents());

        $helloDeleteResponse = $method->invoke($web, $this->createServerRequest('hello', 'DELETE'));
        $this->assertSame(405, $helloDeleteResponse->getStatusCode());

        $apiAuthResponse = $method->invoke($web, $this->createServerRequest('api/auth', 'GET'));
        $this->assertSame([
            'Access-Control-Allow-Origin' => ['*'],
            'Access-Control-Allow-Methods' => ['GET, POST, PUT, DELETE, OPTIONS'],
            'Access-Control-Allow-Headers' => ['X-Requested-With, Content-Type, Accept, Origin, Authorization'],
        ], $apiAuthResponse->getHeaders());
        $this->assertSame(200, $apiAuthResponse->getStatusCode());

        $blogCommentsResponse = $method->invoke($web, $this->createServerRequest('blogs/1/comments', 'GET'));
        $this->assertSame('pages/blogs/[[id]]/comments:1', $blogCommentsResponse->getBody()->__toString());
        $this->assertSame(200, $blogCommentsResponse->getStatusCode());
        $this->assertSame([
            'Content-Type' => ['text/html'],
            'Access-Control-Allow-Origin' => ['*'],
            'Access-Control-Allow-Methods' => ['GET, POST, PUT, DELETE, OPTIONS'],
            'Access-Control-Allow-Headers' => ['X-Requested-With, Content-Type, Accept, Origin, Authorization'],
        ], $blogCommentsResponse->getHeaders());
        
        $renderTestPageWithComponentsAndLayouts = $method->invoke($web, $this->createServerRequest('components-test', 'GET'));
        
        $this->assertSame(
<<<'PHP'
<h1>layoutarg1</h1>
<h1>LAYOUTARG1</h1>
<h2>layoutarg2</h2>
<div><div class="slot1">Slot 1</div>
<div class="alert">alert message</div></div>
<div><div class="nmdSlot2">Named Slot 2</div>
</div>
<div>SQLITE::MEMORY:</div>
<div>SQLITE::MEMORY:</div>
<div>SQLITE::MEMORY:</div>
PHP, $renderTestPageWithComponentsAndLayouts->getBody()->__toString());
        $this->assertSame(200, $renderTestPageWithComponentsAndLayouts->getStatusCode());

        $cfg = new class('foo', 'foo', 'foo', 'foor') extends TestConfig implements ExtensionServiceInterface
        {
            public function events(EventService $service): EventService
            {
                return $service;
            }

            public function extensions(ExtensionService $service): ExtensionService
            {
                return $service->register(new \Tests\Build\Extension(new Db('dsn')));
            }
        };

        $newApp = new Application($cfg);

        $listeners = $newApp->getEventService()->getListeners();

        $this->assertArrayHasKey(AfterBuildEvent::class, $listeners);
        
        $this->destoryTmpFile($app->config->getBuildOutputFolder() . DIRECTORY_SEPARATOR . 'routes.php');
    }

    /**
     * Test that methods arrays are correctly sorted at all levels of nesting
     */
    private function assertMethodsSortedCorrectly(array $routes): void
    {
        // Test top-level methods sorting
        if (isset($routes['hello']['_children']['methods'])) {
            $methods = array_keys($routes['hello']['_children']['methods']);
            $sortedMethods = $methods;
            sort($sortedMethods);
            $this->assertEquals($sortedMethods, $methods, 'Top-level methods should be sorted');
        }

        // Test nested methods sorting (users/index with GET and POST)
        if (isset($routes['users']['_children']['index']['_children']['methods'])) {
            $methods = array_keys($routes['users']['_children']['index']['_children']['methods']);
            $sortedMethods = $methods;
            sort($sortedMethods);
            $this->assertEquals($sortedMethods, $methods, 'Nested methods should be sorted');
            
            // Specifically verify GET comes before POST
            $this->assertEquals(['GET', 'POST'], $methods, 'GET should come before POST in nested structure');
        }

        // Test deeply nested methods sorting (users/[[test]]-methods with GET and POST)
        if (isset($routes['users']['_children']['<test:[a-zA-Z0-9\-]+>-methods']['_children']['methods'])) {
            $methods = array_keys($routes['users']['_children']['<test:[a-zA-Z0-9\-]+>-methods']['_children']['methods']);
            $sortedMethods = $methods;
            sort($sortedMethods);
            $this->assertEquals($sortedMethods, $methods, 'Deeply nested methods should be sorted');
        }

        // Test very deeply nested methods sorting (nested/[[level1]]/[[level2]] with GET, POST, PUT)
        if (isset($routes['nested']['_children']['<level1:[a-zA-Z0-9\-]+>']['_children']['<level2:[a-zA-Z0-9\-]+>']['_children']['methods'])) {
            $methods = array_keys($routes['nested']['_children']['<level1:[a-zA-Z0-9\-]+>']['_children']['<level2:[a-zA-Z0-9\-]+>']['_children']['methods']);
            $sortedMethods = $methods;
            sort($sortedMethods);
            $this->assertEquals($sortedMethods, $methods, 'Very deeply nested methods should be sorted');
            
            // Specifically verify GET comes before POST comes before PUT
            $this->assertEquals(['GET', 'POST', 'PUT'], $methods, 'Methods should be alphabetically sorted in very deep nested structure');
        }
    }
}