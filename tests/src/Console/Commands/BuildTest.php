<?php

namespace Tests\src\Console\Commands;

use Darken\Console\Application;
use Darken\Console\Commands\Build;
use Darken\Events\AfterBuildEvent;
use Darken\Service\EventService;
use Darken\Service\EventServiceInterface;
use Darken\Service\ExtensionInterface;
use Darken\Service\ExtensionService;
use Darken\Service\ExtensionServiceInterface;
use Darken\Web\Application as WebApplication;
use Darken\Web\PageHandler;
use Darken\Web\Request;
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
        $build->clear = true;

        $build->run($app);
        
        $content = include($config->getBuildOutputFolder() . '/routes.php');

        $this->assertSame([
            '<slug:.+>' => [
                '_children' => [
                    'class' => 'Tests\Build\data\pages\slug',
                ],
            ],
            'api' => [
                '_children' => [
                    'auth' => [
                        '_children' => [
                            'middlewares' => [
                                [
                                    'class' => '\Darken\Middleware\AuthenticationMiddleware',
                                    'params' => [
                                        'authHeader' => 'Authorization',
                                        'expectedToken' => 'FooBar',
                                    ],
                                    'position' => '\Darken\Enum\MiddlewarePosition::BEFORE',
                                ],
                            ],
                            'class' => 'Tests\Build\data\pages\api\auth',
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
                                    'middlewares' => [
                                        [
                                            'class' => '\Darken\Middleware\AddCustomHeaderMiddleware',
                                            'params' => [
                                                'name' => 'X-Foo',
                                                'value' => 'X-Bar',
                                            ],
                                            'position' => '\Darken\Enum\MiddlewarePosition::AFTER',
                                        ],
                                    ],
                                    'class' => 'Tests\Build\data\pages\blogs\id\comments',
                                ],
                            ],
                            'index' => [
                                '_children' => [
                                    'class' => 'Tests\Build\data\pages\blogs\id\index',
                                ],
                            ],
                        ],
                    ],
                    'index' => [
                        '_children' => [
                            'class' => 'Tests\Build\data\pages\blogs\index',
                        ],
                    ],
                ],
            ],
            'components-test' => [
                '_children' => [
                    'class' => 'Tests\Build\data\pages\componentstest',
                ],
            ],
            'hello' => [
                '_children' => [
                    'class' => 'Tests\Build\data\pages\hello',
                ],
            ],
            'params' =>  [
                '_children' =>  [
                    'middlewares' =>  [
                        0 => [
                            'class' => '\Darken\Middleware\CorsMiddlware',
                            'params' => [],
                            'position' => 'before',
                        ],
                    ],
                    'class' => 'Tests\Build\data\pages\params',
                ],
            ],
        ], $content);

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
                200, '{"message":"auth-api"}' // middlware is not extraed and processed in page handler!
            ],
            'params' => [
                200, 'pages/params'
            ],
        ] as $path => $def) {

            $handler = new PageHandler($web, $path);
            $response = $handler->handle($this->createServerRequest($path, 'GET'));

            $this->assertInstanceOf(ResponseInterface::class, $response);

            $this->assertSame($def[0], $response->getStatusCode(), "Failed for GET path: $path");
            $this->assertSame($def[1], (string) $response->getBody(), "Failed for GET path: $path");
        }
        
        $reflection = new ReflectionClass($web);
        $method = $reflection->getMethod('handleServerRequest');
        $method->setAccessible(true);

        $apiAuthResponse = $method->invoke($web, $this->createServerRequest('api/auth', 'GET'));
        $this->assertSame('{"error":"Unauthorized"}', $apiAuthResponse->getBody()->__toString());
        $this->assertSame(401, $apiAuthResponse->getStatusCode());

        $blogCommentsResponse = $method->invoke($web, $this->createServerRequest('blogs/1/comments', 'GET'));
        $this->assertSame('pages/blogs/[[id]]/comments:1', $blogCommentsResponse->getBody()->__toString());
        $this->assertSame(200, $blogCommentsResponse->getStatusCode());
        $this->assertSame([
            'Content-Type' => ['text/html'],
            'X-Foo' => ['X-Bar'],
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

        $listeners = $newApp->getEventService()->getListeneres();

        $this->assertArrayHasKey(AfterBuildEvent::class, $listeners);
        
    }
}