<?php

namespace Tests\src\Console\Commands;

use Darken\Console\Commands\Build;
use Tests\TestCase;
use ReflectionMethod;
use ReflectionClass;

class BuildMethodDebugTest extends TestCase
{
    public function testSortMethodsDebugging()
    {
        $build = new Build();
        $reflection = new ReflectionClass($build);
        $method = $reflection->getMethod('sortMethodsRecursively');
        $method->setAccessible(true);

        // Test direct methods
        $trie2 = [
            'test_node' => [
                'methods' => [
                    'POST' => ['class' => 'TestClass'],
                    'GET' => ['class' => 'TestClass'],
                    'PUT' => ['class' => 'TestClass'],
                ]
            ]
        ];

    $before = array_keys($trie2['test_node']['methods']);

    $method->invokeArgs($build, [&$trie2]);

    $after = array_keys($trie2['test_node']['methods']);

    // Ensure that methods were reordered and sorted
    $this->assertEquals(['GET', 'POST', 'PUT'], $after);
    $this->assertNotEquals($before, $after);
    }
}
