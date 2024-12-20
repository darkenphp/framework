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
            'index' => 200,
            '' => 200,
            'hello' => 200,
            'blogs' => 200,
            'blogs/1' => 200,
            'blogs/1/comments' => 200,
        ] as $path => $status) {

            $handler = new PageHandler($web, $path);
            $response = $handler->handle($this->createServerRequest('GET',  $path));

            $this->assertInstanceOf(ResponseInterface::class, $response);

            $this->assertSame($status, $response->getStatusCode());

            //echo $response->getBody();
        }
    }
}