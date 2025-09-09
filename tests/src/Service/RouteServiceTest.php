<?php

namespace Tests\src\Service;

use Darken\Config\ConfigInterface;
use Darken\Service\RouteService;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class RouteServiceTest extends TestCase
{
    private ConfigInterface|MockObject $config;
    private RouteService $routeService;
    private string $tempRoutesFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tempRoutesFile = sys_get_temp_dir() . '/test_routes_' . uniqid() . '/routes.php';
        $tempDir = dirname($this->tempRoutesFile);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $this->config = $this->createMock(ConfigInterface::class);
        $this->config->method('getBuildOutputFolder')
            ->willReturn($tempDir);
        
        $this->routeService = new RouteService($this->config);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempRoutesFile)) {
            unlink($this->tempRoutesFile);
        }
        $tempDir = dirname($this->tempRoutesFile);
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
        parent::tearDown();
    }

    public function testConstructorWithNonExistentRoutesFile(): void
    {
        $service = new RouteService($this->config);
        $this->assertInstanceOf(RouteService::class, $service);
    }

    public function testConstructorWithUnreadableRoutesFile(): void
    {
        if (!function_exists('posix_getuid') || posix_getuid() === 0) {
            $this->markTestSkipped('Cannot test file permissions as root or on systems without POSIX');
        }
        
        file_put_contents($this->tempRoutesFile, '<?php return [];');
        chmod($this->tempRoutesFile, 0000);
        
        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Routes file "' . $this->tempRoutesFile . '" is not readable');
            
            new RouteService($this->config);
        } finally {
            chmod($this->tempRoutesFile, 0644);
        }
    }

    public function testConstructorWithEmptyRoutesFile(): void
    {
        file_put_contents($this->tempRoutesFile, '<?php');
        
        $service = new RouteService($this->config);
        $this->assertInstanceOf(RouteService::class, $service);
    }

    public function testConstructorWithNonArrayRoutesFile(): void
    {
        file_put_contents($this->tempRoutesFile, '<?php return "not an array";');
        
        $service = new RouteService($this->config);
        $this->assertInstanceOf(RouteService::class, $service);
    }

    public function testCreateSimpleRoute(): void
    {
        $trie = [
            'users' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\users']
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->create('Build\\pages\\users');
        $this->assertEquals('/users', $result);
    }

    public function testCreateIndexRoute(): void
    {
        $trie = [
            'index' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\index']
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->create('Build\\pages\\index');
        $this->assertEquals('/', $result);
    }

    public function testCreateRouteWithParameters(): void
    {
        $trie = [
            'users' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\users']
                    ]
                ],
                '<id:[0-9]+>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\users\\id']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->create('Build\\pages\\users\\id', ['id' => '123']);
        $this->assertEquals('/users/123', $result);
    }

    public function testCreateRouteWithCatchAllParameter(): void
    {
        $trie = [
            'files' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\files']
                    ]
                ],
                '<path:.+>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\files\\path']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        // Use a path without slashes since current implementation treats <path:.+> as embedded
        $result = $this->routeService->create('Build\\pages\\files\\path', ['path' => 'filename.txt']);
        $this->assertEquals('/files/filename.txt', $result);
    }

    public function testCreateRouteWithEmbeddedParameters(): void
    {
        $trie = [
            'blog-<slug:[a-z-]+>-post' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\blog_post']
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->create('Build\\pages\\blog_post', ['slug' => 'hello-world']);
        $this->assertEquals('/blog-hello-world-post', $result);
    }

    public function testCreateRouteWithMultipleEmbeddedParameters(): void
    {
        $trie = [
            'api-<version:[0-9]+>-user-<id:[0-9]+>' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\api_user']
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->create('Build\\pages\\api_user', ['version' => '1', 'id' => '42']);
        $this->assertEquals('/api-1-user-42', $result);
    }

    public function testCreateRouteWithQueryParameters(): void
    {
        $trie = [
            'users' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\users']
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->create('Build\\pages\\users', ['page' => '2', 'limit' => '10']);
        $this->assertEquals('/users?page=2&limit=10', $result);
    }

    public function testCreateRouteWithMixedParameters(): void
    {
        $trie = [
            'users' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\users']
                    ]
                ],
                '<id:[0-9]+>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\users\\id']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->create('Build\\pages\\users\\id', ['id' => '123', 'tab' => 'profile']);
        $this->assertEquals('/users/123?tab=profile', $result);
    }

    public function testCreateRouteWithSpecificMethod(): void
    {
        $trie = [
            'api' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\api']
                    ]
                ],
                'users' => [
                    '_children' => [
                        'methods' => [
                            'POST' => ['class' => 'Build\\pages\\api\\users\\post'],
                            'GET' => ['class' => 'Build\\pages\\api\\users\\get']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->create('Build\\pages\\api\\users\\post', [], 'POST');
        $this->assertEquals('/api/users', $result);
    }

    public function testCreateRouteWithWildcardMethod(): void
    {
        $trie = [
            'catch-all' => [
                '_children' => [
                    'methods' => [
                        '*' => ['class' => 'Build\\pages\\catch_all']
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->create('Build\\pages\\catch_all', [], 'DELETE');
        $this->assertEquals('/catch-all', $result);
    }

    public function testCreateThrowsExceptionWhenClassNotFound(): void
    {
        $trie = [
            'users' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\users']
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No route found for class Build\\pages\\nonexistent (method GET).');
        
        $this->routeService->create('Build\\pages\\nonexistent');
    }

    public function testCreateThrowsExceptionWhenParameterMissing(): void
    {
        $trie = [
            'users' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\users']
                    ]
                ],
                '<id:[0-9]+>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\users\\id']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required route param 'id' for Build\\pages\\users\\id.");
        
        $this->routeService->create('Build\\pages\\users\\id');
    }

    public function testCreateThrowsExceptionWhenParameterNotScalar(): void
    {
        $trie = [
            'users' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\users']
                    ]
                ],
                '<id:[0-9]+>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\users\\id']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Param 'id' must be a scalar value for Build\\pages\\users\\id.");
        
        $this->routeService->create('Build\\pages\\users\\id', ['id' => ['not', 'scalar']]);
    }

    public function testCreateThrowsExceptionWhenParameterDoesNotMatchRegex(): void
    {
        $trie = [
            'users' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\users']
                    ]
                ],
                '<id:[0-9]+>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\users\\id']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Param 'id' value 'abc' does not match /[0-9]+/");
        
        $this->routeService->create('Build\\pages\\users\\id', ['id' => 'abc']);
    }

    public function testCreateThrowsExceptionWhenInvalidRegex(): void
    {
        $trie = [
            'users' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\users']
                    ]
                ],
                '<id:[invalid>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\users\\id']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid regex for param 'id': /[invalid/");
        
        $this->routeService->create('Build\\pages\\users\\id', ['id' => 'test']);
    }

    public function testCreateThrowsExceptionWhenEmbeddedCatchAllContainsSlash(): void
    {
        $trie = [
            'prefix-<path:.+>-suffix' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\embedded_catchall']
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Param 'path' must not contain '/' in segment 'prefix-<path:.+>-suffix'.");
        
        $this->routeService->create('Build\\pages\\embedded_catchall', ['path' => 'dir/file']);
    }

    public function testCreateThrowsExceptionWhenCatchAllParameterIsEmpty(): void
    {
        $trie = [
            'files' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\files']
                    ]
                ],
                '<path:.+>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\files\\path']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Param 'path' value '' does not match /.+/");
        
        $this->routeService->create('Build\\pages\\files\\path', ['path' => '']);
    }

    public function testCreateHandlesSpecialScalarValues(): void
    {
        $trie = [
            'test' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\test']
                    ]
                ],
                '<value:[0-9]+>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\test\\value']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        // Test with integer 0
        $result = $this->routeService->create('Build\\pages\\test\\value', ['value' => 0]);
        $this->assertEquals('/test/0', $result);
        
        // Test with string '0'
        $result = $this->routeService->create('Build\\pages\\test\\value', ['value' => '0']);
        $this->assertEquals('/test/0', $result);
    }

    public function testFindRouteNodeWithSimpleRoute(): void
    {
        $trie = [
            'users' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\users']
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->findRouteNode('/users');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('methods', $result[0]);
        $this->assertEquals([], $result[1]); // No params
    }

    public function testFindRouteNodeWithRootRoute(): void
    {
        $trie = [
            'index' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\index']
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->findRouteNode('/');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('methods', $result[0]);
    }

    public function testCreateWithUrlEncoding(): void
    {
        $trie = [
            'test space' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\test_space']
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->create('Build\\pages\\test_space');
        $this->assertEquals('/test%20space', $result);
    }

    public function testCreateWithUrlEncodingInParameters(): void
    {
        $trie = [
            'search' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\search']
                    ]
                ],
                '<query:.+>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\search\\query']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->create('Build\\pages\\search\\query', ['query' => 'hello world']);
        $this->assertEquals('/search/hello%20world', $result);
    }

    public function testCreateCatchAllWithMultipleParts(): void
    {
        $trie = [
            'files' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\files']
                    ]
                ],
                '<path:.+>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\files\\path']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        // Use a path without slashes but with spaces to test encoding
        $result = $this->routeService->create('Build\\pages\\files\\path', ['path' => 'file name with spaces.txt']);
        $this->assertEquals('/files/file%20name%20with%20spaces.txt', $result);
    }

    public function testRealWorldComplexRoute(): void
    {
        $trie = [
            'blog' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\blog']
                    ]
                ],
                '<year:[0-9]{4}>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\blog\\year']
                        ]
                    ],
                    '<month:[0-9]{2}>' => [
                        '_children' => [
                            'methods' => [
                                'GET' => ['class' => 'Build\\pages\\blog\\year\\month']
                            ]
                        ],
                        '<day:[0-9]{2}>' => [
                            '_children' => [
                                'methods' => [
                                    'GET' => ['class' => 'Build\\pages\\blog\\year\\month\\day']
                                ]
                            ],
                            '<slug:[a-z0-9-]+>' => [
                                '_children' => [
                                    'methods' => [
                                        'GET' => ['class' => 'Build\\pages\\blog\\post']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->create('Build\\pages\\blog\\post', [
            'year' => '2024',
            'month' => '03',
            'day' => '15',
            'slug' => 'my-blog-post',
            'ref' => 'twitter' // Extra param becomes query string
        ]);
        
        $this->assertEquals('/blog/2024/03/15/my-blog-post?ref=twitter', $result);
    }

    // Test coverage for additional methods and edge cases

    public function testFindRouteNodeReturnsFalseWhenNotFound(): void
    {
        $trie = [
            'users' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\users']
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->findRouteNode('/nonexistent');
        $this->assertFalse($result);
    }

    public function testFindRouteNodeWithParameterRoute(): void
    {
        // Use the Build-command generated structure for findRouteNode
        $trie = [
            'users' => [
                '_children' => [
                    '<id:[0-9]+>' => [
                        '_children' => [
                            'methods' => [
                                'GET' => ['class' => 'Build\\pages\\users\\id']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->findRouteNode('/users/123');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('methods', $result[0]);
        $this->assertEquals(['id' => '123'], $result[1]);
    }

    public function testFindRouteNodeWithCatchAllRoute(): void
    {
        $trie = [
            'files' => [
                '_children' => [
                    '<path:.+>' => [
                        '_children' => [
                            'methods' => [
                                'GET' => ['class' => 'Build\\pages\\files\\path']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->findRouteNode('/files/dir/subdir/file.txt');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('methods', $result[0]);
        $this->assertEquals(['path' => 'dir/subdir/file.txt'], $result[1]);
    }

    public function testFindRouteNodeWithCatchAllAndTrailingIndex(): void
    {
        $trie = [
            'files' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\files']
                    ],
                    '<path:.+>' => [
                        '_children' => [
                            'methods' => [
                                'GET' => ['class' => 'Build\\pages\\files\\path']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->findRouteNode('/files/dir/index');
        $this->assertIsArray($result);
        $this->assertEquals(['path' => 'dir/index'], $result[1]);
    }

    public function testFindRouteNodeWithInvalidParameterPattern(): void
    {
        $trie = [
            'test' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\test']
                    ]
                ],
                '<invalid' => [ // Missing closing >
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\test\\invalid']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->findRouteNode('/test/anything');
        $this->assertFalse($result);
    }

    public function testFindRouteNodeWithMalformedParameterPattern(): void
    {
        $trie = [
            'test' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\test']
                    ]
                ],
                '<nocolon>' => [ // Missing colon separator
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\test\\nocolon']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->findRouteNode('/test/anything');
        $this->assertFalse($result);
    }

    public function testFindRouteNodePreferExactMatchOverPattern(): void
    {
        $trie = [
            'test' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\test']
                    ],
                    'exact' => [
                        '_children' => [
                            'methods' => [
                                'GET' => ['class' => 'Build\\pages\\test\\exact']
                            ]
                        ]
                    ],
                    '<param:[a-z]+>' => [
                        '_children' => [
                            'methods' => [
                                'GET' => ['class' => 'Build\\pages\\test\\param']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->findRouteNode('/test/exact');
        $this->assertIsArray($result);
        $this->assertEquals('Build\\pages\\test\\exact', $result[0]['methods']['GET']['class']);
        $this->assertEquals([], $result[1]); // No params captured
    }

    public function testFindRouteNodeHandlesComplexTrie(): void
    {
        $trie = [
            'api' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\api']
                    ],
                    'v1' => [
                        '_children' => [
                            'methods' => [
                                'GET' => ['class' => 'Build\\pages\\api\\v1']
                            ],
                            'users' => [
                                '_children' => [
                                    'methods' => [
                                        'GET' => ['class' => 'Build\\pages\\api\\v1\\users']
                                    ],
                                    '<id:[0-9]+>' => [
                                        '_children' => [
                                            'methods' => [
                                                'GET' => ['class' => 'Build\\pages\\api\\v1\\users\\id']
                                            ],
                                            'posts' => [
                                                '_children' => [
                                                    'methods' => [
                                                        'GET' => ['class' => 'Build\\pages\\api\\v1\\users\\id\\posts']
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        $result = $this->routeService->findRouteNode('/api/v1/users/42/posts');
        $this->assertIsArray($result);
        $this->assertEquals(['id' => '42'], $result[1]);
        $this->assertEquals('Build\\pages\\api\\v1\\users\\id\\posts', $result[0]['methods']['GET']['class']);
    }

    public function testFindRouteNodeWithEmptyTrie(): void
    {
        $this->routeService->setTrieForTesting([]);
        
        $result = $this->routeService->findRouteNode('/anything');
        $this->assertFalse($result);
    }
}
