<?php

namespace Tests\src\Console\Commands;

use Darken\Console\Commands\Build;
use Tests\TestCase;
use ReflectionClass;

class SortMethodsCoverageTest extends TestCase
{
    /**
     * Test to specifically ensure code coverage of sortMethodsRecursively method
     */
    public function testSortMethodsRecursivelyCoverage()
    {
        $build = new Build();
        $reflection = new ReflectionClass($build);
        $method = $reflection->getMethod('sortMethodsRecursively');
        $method->setAccessible(true);

        // This test is specifically designed to hit all code paths in sortMethodsRecursively

        // Test 1: Hit the continue line with non-array values
        $trieContinue = [
            'string' => 'not_an_array',        // continue
            'null' => null,                     // continue
            'integer' => 42,                    // continue
            'boolean' => false,                 // continue
        ];

    $method->invokeArgs($build, [&$trieContinue]);

        // Verify non-arrays remain unchanged (continue was hit)
        $this->assertEquals('not_an_array', $trieContinue['string']);
        $this->assertNull($trieContinue['null']);
        $this->assertEquals(42, $trieContinue['integer']);
        $this->assertFalse($trieContinue['boolean']);

        // Test 2: Hit the ksort line with direct methods
        $trieKsort = [
            'route' => [
                'methods' => [
                    'POST' => ['class' => 'Test'],
                    'GET' => ['class' => 'Test'],
                    'PUT' => ['class' => 'Test'],
                    'DELETE' => ['class' => 'Test'],
                ]
            ]
        ];

    $method->invokeArgs($build, [&$trieKsort]);

        // Verify methods are sorted (ksort was hit)
        $keys = array_keys($trieKsort['route']['methods']);
        $this->assertEquals(['DELETE', 'GET', 'POST', 'PUT'], $keys);

        // Test 3: Hit the recursive call with _children
        $trieRecursive = [
            'parent' => [
                '_children' => [
                    'child' => [
                        'methods' => [
                            'Z' => ['class' => 'Test'],
                            'A' => ['class' => 'Test'],
                            'M' => ['class' => 'Test'],
                        ]
                    ]
                ]
            ]
        ];

    $method->invokeArgs($build, [&$trieRecursive]);

        // Verify nested methods are sorted (recursive call + ksort was hit)
        $nestedKeys = array_keys($trieRecursive['parent']['_children']['child']['methods']);
        $this->assertEquals(['A', 'M', 'Z'], $nestedKeys);

        // Test 4: Complex structure to hit multiple paths
        $trieComplex = [
            'non_array' => 'skip_me',           // continue
            'direct_methods' => [
                'methods' => [
                    'PATCH' => ['class' => 'Test'],
                    'HEAD' => ['class' => 'Test'],
                ]
            ],
            'with_children' => [
                '_children' => [
                    'deeper' => [
                        'methods' => [
                            'OPTIONS' => ['class' => 'Test'],
                            'CONNECT' => ['class' => 'Test'],
                        ],
                        '_children' => [
                            'deepest' => [
                                'methods' => [
                                    'TRACE' => ['class' => 'Test'],
                                    'CUSTOM' => ['class' => 'Test'],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

    $method->invokeArgs($build, [&$trieComplex]);

        // Verify all levels were processed correctly
        $this->assertEquals('skip_me', $trieComplex['non_array']);
        $this->assertEquals(['HEAD', 'PATCH'], array_keys($trieComplex['direct_methods']['methods']));
        $this->assertEquals(['CONNECT', 'OPTIONS'], array_keys($trieComplex['with_children']['_children']['deeper']['methods']));
        $this->assertEquals(['CUSTOM', 'TRACE'], array_keys($trieComplex['with_children']['_children']['deeper']['_children']['deepest']['methods']));
    }
}
