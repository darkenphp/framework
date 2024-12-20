<?php

namespace Tests\src\Web;

use Darken\Web\PageHandler;
use ReflectionClass;
use Tests\TestCase;

class PageHandlerTest extends TestCase
{
    private PageHandler $pageHandler;

    public function findRoutes() {
        
        $pageHandler = $this->getMockBuilder(PageHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new ReflectionClass($pageHandler);
        $method = $reflection->getMethod('findRouteNode');
        $method->setAccessible(true);

        $args = func_get_args();
        return $method->invoke($pageHandler, $args[0], $args[1]);
    }
    

    public function testFindRouteNode(): void
    {
        $trie = [
            'api' => [
                '_children' => [
                    'auth' => [
                        '_children' => [
                            'class' => 'Tests\\Build\\data\\pages\\api\\auth',
                        ],
                    ],
                ],
            ],
            '<slug:.+>' => [
                '_children' => [
                    'class' => 'Tests\\Build\\data\\pages\\slug',
                ],
            ],
            'blogs' => [
                '_children' => [
                    '<id:[a-zA-Z0-9\\-]+>' => [
                        '_children' => [
                            'comments' => [
                                '_children' => [
                                    'class' => 'Tests\\Build\\data\\pages\\blogs\\id\\comments',
                                ],
                            ],
                            'index' => [
                                '_children' => [
                                    'class' => 'Tests\\Build\\data\\pages\\blogs\\id\\index',
                                ],
                            ],
                        ],
                    ],
                    'index' => [
                        '_children' => [
                            'class' => 'Tests\\Build\\data\\pages\\blogs\\index',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->findRoutes('foo/bar/baz', $trie);
        $this->assertEquals([
            ['class' => 'Tests\\Build\\data\\pages\\slug'],
            ['slug' => 'foo/bar/baz'],
        ], $result);

        // Case 1: Exact match for a static route
        $result = $this->findRoutes('api/auth', $trie);
        $this->assertEquals([
            ['class' => 'Tests\\Build\\data\\pages\\api\\auth'],
            [],
        ], $result);

        // Case 3: Dynamic match with <id:[a-zA-Z0-9\\-]+>
        $result = $this->findRoutes('blogs/my-blog-id', $trie);
        $this->assertEquals([
            ['class' => 'Tests\\Build\\data\\pages\\blogs\\id\\index'],
            ['id' => 'my-blog-id'],
        ], $result);

        // Case 4: Dynamic match with additional segment (comments)
        $result = $this->findRoutes('blogs/my-blog-id/comments', $trie);
        $this->assertEquals([
            ['class' => 'Tests\\Build\\data\\pages\\blogs\\id\\comments'],
            ['id' => 'my-blog-id'],
        ], $result);

        // Case 5: Root route
        $result = $this->findRoutes('/', $trie);
        $this->assertEquals([
            ['class' => 'Tests\Build\data\pages\slug'],
            ['slug' => ''],
        ], $result);


        // Case 7: Static route with index fallback
        $result = $this->findRoutes('blogs', $trie);
        $this->assertEquals([
            ['class' => 'Tests\\Build\\data\\pages\\blogs\\index'],
            [],
        ], $result);

    }

    public function testWithouWildCardToHave404()
    {
        $trie = [
            'index' => [
                '_children' => [
                    'class' => 'Tests\\Build\\data\\pages\\blogs\\index',
                ],
            ],
            'hello' => [
                '_children' => [
                    'class' => 'Tests\\Build\\data\\pages\\hello',
                ],
            ],
        ];

        $result = $this->findRoutes('does/not/exist', $trie);
        $this->assertFalse($result);

        $result = $this->findRoutes('', $trie); // index => is home page
        $this->assertSame([
            ['class' => 'Tests\\Build\\data\\pages\\blogs\\index'],
            [],
        ], $result);

        $result = $this->findRoutes('hello', $trie); // 
        $this->assertSame([
            ['class' => 'Tests\\Build\\data\\pages\\hello'],
            [],
        ], $result);

        $result = $this->findRoutes('hello/', $trie); // hello with trailing slash
        $this->assertSame([
            ['class' => 'Tests\\Build\\data\\pages\\hello'],
            [],
        ], $result);

        $result = $this->findRoutes('/hello/', $trie); // hello with trailing slash and starting slash
        $this->assertSame([
            ['class' => 'Tests\\Build\\data\\pages\\hello'],
            [],
        ], $result);

        $result = $this->findRoutes('does/not/with/trailing/', $trie);
        $this->assertFalse($result);
    }

    public function testWildCardButInSubFolder()
    {
        $trie = [
            'root' => [
                '_children' => [
                    'class' => 'root',
                ],
            ],
            'blogs' => [
                '_children' => [
                    '<slug:.+>' => [
                        '_children' => [
                            'class' => 'blogsslugwildcard',
                        ],
                    ],
                    'comments' => [
                        '_children' => [
                            'class' => 'blogscomment',
                        ],
                    ],
                    'index' => [
                        '_children' => [
                            'class' => 'blogsindex',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertFalse($this->findRoutes('', $trie));
        $this->assertFalse($this->findRoutes('/', $trie));
        $this->assertFalse($this->findRoutes('doesnotexists', $trie));
        $this->assertSame([
            ['class' => 'root'],
            [],
        ], $this->findRoutes('root', $trie));
        $this->assertSame([
            ['class' => 'blogsindex'],
            [],
        ], $this->findRoutes('/blogs', $trie));
        $this->assertSame([
            ['class' => 'blogsslugwildcard'],
            ['slug' => 'my-blog-id'],
        ], $this->findRoutes('blogs/my-blog-id', $trie));
        $this->assertSame([
            ['class' => 'blogsslugwildcard'],
            ['slug' => 'my-blog-id/more'],
        ], $this->findRoutes('blogs/my-blog-id/more', $trie));
        $this->assertSame([
            ['class' => 'blogscomment'],
            [],
        ], $this->findRoutes('blogs/comments', $trie));


        /*

        $result = $this->findRoutes('blogs/1/comments', $trie);
        $this->assertSame([
            ['class' => 'Tests\\Build\\data\\pages\\blogs\\id\\comments'],
            ['id' => '1'],
        ], $result);

        $result = $this->findRoutes('blogs/1/comments/', $trie);
        $this->assertSame([
            ['class' => 'Tests\\Build\\data\\pages\\blogs\\id\\comments'],
            ['id' => '1'],
        ], $result);

        $result = $this->findRoutes('blogs/1/comments/1', $trie);
        $this->assertFalse($result);

        $result = $this->findRoutes('blogs/1/comments/1/', $trie);
        $this->assertFalse($result);

        $result = $this->findRoutes('blogs/1/comments/1/2', $trie);
        $this->assertFalse($result);
        */
    }
}
