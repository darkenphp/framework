<?php

namespace Tests\src\Builder\Console\Commands;

use Darken\Console\Application;
use Darken\Console\Commands\Build;
use Tests\TestCase;
use Tests\TestConfig;

class BuildTest extends TestCase
{
    public function testBuild()
    {
        $config = new TestConfig(
            rootDirectoryPath: $this->getTestsRootFolder(),
            pagesFolder: 'data/pages',
            builderOutputFolder: '.build',
            componentsFolder: 'data/components'
        );

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
                    "class" => "Build\\data\\pages\\slug",
                    "middlewares" => []
                ]
            ],
            "blogs" => [
                "_children" => [
                    "<id:[a-zA-Z0-9\\-]+>" => [
                        "_children" => [
                            "comments" => [
                                "_children" => [
                                    "class" => "Build\\data\\pages\\blogs\\[[id]]\\comments",
                                    "middlewares" => []
                                ]
                            ],
                            "index" => [
                                "_children" => [
                                    "class" => "Build\\data\\pages\\blogs\\[[id]]\\index",
                                    "middlewares" => []
                                ]
                            ]
                        ]
                    ],
                    "index" => [
                        "_children" => [
                            "class" => "Build\\data\\pages\\blogs\\index",
                            "middlewares" => []
                        ]
                    ]
                ]
            ],
            "hello" => [
                "_children" => [
                    "class" => "Build\\data\\pages\\hello",
                    "middlewares" => []
                ]
            ]
        ], $content);

        
    }
}