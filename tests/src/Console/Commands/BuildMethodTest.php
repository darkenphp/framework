<?php

namespace Tests\src\Console\Commands;

use Darken\Console\Commands\Build;
use Tests\TestCase;
use ReflectionMethod;
use ReflectionClass;

class BuildMethodTest extends TestCase
{
    public function testSortMethodsRecursivelyWithVariousStructures()
    {
        $build = new Build();
        $reflection = new ReflectionClass($build);
        $method = $reflection->getMethod('sortMethodsRecursively');
        $method->setAccessible(true);

        // Test case 1: Non-array values (to hit the continue line)
        $trie1 = [
            'simple_string' => 'not_an_array',  // This should trigger continue
            'null_value' => null,               // This should also trigger continue
            'number' => 42,                     // This should also trigger continue
            'valid_node' => [
                'methods' => [
                    'POST' => ['class' => 'TestClass'],
                    'GET' => ['class' => 'TestClass'],
                    'PUT' => ['class' => 'TestClass'],
                ]
            ]
        ];

        // Create a copy to compare keys before sorting
        $beforeKeys = array_keys($trie1['valid_node']['methods']);
        
    $method->invokeArgs($build, [&$trie1]);

        // Verify that the non-array values were skipped
        $this->assertEquals('not_an_array', $trie1['simple_string']);
        $this->assertNull($trie1['null_value']);
        $this->assertEquals(42, $trie1['number']);
        
        // Verify methods were sorted
        $afterKeys = array_keys($trie1['valid_node']['methods']);
        $this->assertEquals(['GET', 'POST', 'PUT'], $afterKeys);
        $this->assertNotEquals($beforeKeys, $afterKeys); // Should be different order

        // Test case 2: Structure with _children (to hit the recursive call)
        $trie2 = [
            'route' => [
                '_children' => [
                    'subroute' => [
                        'methods' => [
                            'DELETE' => ['class' => 'TestClass'],
                            'POST' => ['class' => 'TestClass'],
                            'GET' => ['class' => 'TestClass'],
                        ]
                    ]
                ]
            ]
        ];

    $method->invokeArgs($build, [&$trie2]);

        // Verify methods were sorted in the nested structure
        $this->assertEquals(['DELETE', 'GET', 'POST'], array_keys($trie2['route']['_children']['subroute']['methods']));

        // Test case 3: Mixed structure with both direct methods and _children
        $trie3 = [
            'false_value' => false,  // Another continue case
            'node_with_methods' => [
                'methods' => [
                    'PATCH' => ['class' => 'TestClass'],
                    'GET' => ['class' => 'TestClass'],
                    'DELETE' => ['class' => 'TestClass'],
                ]
            ],
            'node_with_children' => [
                '_children' => [
                    'deeply_nested' => [
                        'methods' => [
                            'PUT' => ['class' => 'TestClass'],
                            'DELETE' => ['class' => 'TestClass'],
                            'GET' => ['class' => 'TestClass'],
                        ],
                        '_children' => [
                            'even_deeper' => [
                                'methods' => [
                                    'TRACE' => ['class' => 'TestClass'],
                                    'OPTIONS' => ['class' => 'TestClass'],
                                    'HEAD' => ['class' => 'TestClass'],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

    $method->invokeArgs($build, [&$trie3]);

        // Verify all methods at all levels were sorted
        $this->assertFalse($trie3['false_value']); // Should remain unchanged
        $this->assertEquals(['DELETE', 'GET', 'PATCH'], array_keys($trie3['node_with_methods']['methods']));
        $this->assertEquals(['DELETE', 'GET', 'PUT'], array_keys($trie3['node_with_children']['_children']['deeply_nested']['methods']));
        $this->assertEquals(['HEAD', 'OPTIONS', 'TRACE'], array_keys($trie3['node_with_children']['_children']['deeply_nested']['_children']['even_deeper']['methods']));
    }

    public function testSortMethodsRecursivelyWithEmptyStructures()
    {
        $build = new Build();
        $reflection = new ReflectionClass($build);
        $method = $reflection->getMethod('sortMethodsRecursively');
        $method->setAccessible(true);

        // Test with empty and edge case structures
        $trie = [
            'empty_array' => [],                 // Should not break
            'no_methods_or_children' => [
                'some_other_key' => 'value'
            ],
            'empty_methods' => [
                'methods' => []                  // Should not break
            ],
            'empty_children' => [
                '_children' => []                // Should not break
            ]
        ];

        // This should not throw any errors
    $method->invokeArgs($build, [&$trie]);
        
        // Verify structure remains intact
        $this->assertEquals([], $trie['empty_array']);
        $this->assertEquals(['some_other_key' => 'value'], $trie['no_methods_or_children']);
        $this->assertEquals([], $trie['empty_methods']['methods']);
        $this->assertEquals([], $trie['empty_children']['_children']);
    }
}
