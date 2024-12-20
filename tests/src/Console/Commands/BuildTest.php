<?php

namespace Tests\src\Builder\Console\Commands;

use Darken\Console\Application;
use Darken\Console\Commands\Build;
use Darken\Web\Application as WebApplication;
use Darken\Web\PageHandler;
use Psr\Http\Message\ResponseInterface;
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

        $build = new Build();
        $build->clear = true;

        $build->run($app);
        
        $content = include($config->getBuildOutputFolder() . '/routes.php');

        $this->assertSame([
            "<slug:.+>" => [
                "_children" => [
                    "class" => "Tests\\Build\\data\\pages\\slug",
                    "middlewares" => []
                ]
            ],
            'api' => [
                '_children' => [
                    'auth' => [
                        '_children' => [
                            'class' => 'Tests\\Build\\data\\pages\\api\\auth',
                            'middlewares' => [
                                [
                                    'class' => '\\Darken\\Middleware\\AddCustomHeaderMiddleware',
                                    'params' => [
                                        'name' => 'Content-Type',
                                        'value' => 'application/json',
                                    ],
                                    'position' => '\\Darken\\Enum\\MiddlewarePosition::AFTER',
                                ],
                                [
                                    'class' => '\\Darken\\Middleware\\AuthenticationMiddleware',
                                    'params' => [
                                        'authHeader' => 'Authorization',
                                        'expectedToken' => 'FooBar',
                                    ],
                                    'position' => '\\Darken\\Enum\\MiddlewarePosition::BEFORE',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            "blogs" => [
                "_children" => [
                    "<id:[a-zA-Z0-9\\-]+>" => [
                        "_children" => [
                            "comments" => [
                                "_children" => [
                                    "class" => "Tests\\Build\\data\\pages\\blogs\\id\\comments",
                                    "middlewares" => []
                                ]
                            ],
                            "index" => [
                                "_children" => [
                                    "class" => "Tests\\Build\\data\\pages\\blogs\\id\\index",
                                    "middlewares" => []
                                ]
                            ]
                        ]
                    ],
                    "index" => [
                        "_children" => [
                            "class" => "Tests\\Build\\data\\pages\\blogs\\index",
                            "middlewares" => []
                        ]
                    ]
                ]
            ],
            "hello" => [
                "_children" => [
                    "class" => "Tests\\Build\\data\\pages\\hello",
                    "middlewares" => []
                ]
            ]
        ], $content);


        // web app
        $web = new WebApplication($config);
        $web->whoops->unregister();

        foreach ([
            'index' => [
                200, 'pages/[[...slug]]:index'
            ],
            '' => [
                200, 'pages/[[...slug]]:index' // this is wrong!
            ],
            /*
            'does/not/exist/but/wildcard' => [
                200, 'pages/[[...slug]]:does/not/exist/but/wildcard'
            ],
            'does/not/with/trailing/' => [
                200, 'pages/[[...slug]]:does/not/with/trailing'
            ],
            */
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
        ] as $path => $def) {

            $handler = new PageHandler($web, $path);
            $response = $handler->handle($this->createServerRequest('GET',  $path));

            $this->assertInstanceOf(ResponseInterface::class, $response);

            $this->assertSame($def[0], $response->getStatusCode(), "Failed for GET path: $path");
            $this->assertSame($def[1], (string) $response->getBody(), "Failed for GET path: $path");
        }
    }
}