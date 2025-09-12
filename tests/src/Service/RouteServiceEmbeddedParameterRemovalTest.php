<?php

namespace Tests\src\Service;

use Darken\Service\RouteService;
use Darken\Config\ConfigInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test to verify that embedded parameters are no longer needed with the prefix system
 * The new prefix system solves embedded parameters at route generation time, not matching time
 */
class RouteServiceEmbeddedParameterRemovalTest extends TestCase
{
    private RouteService $routeService;
    private ConfigInterface|MockObject $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = $this->createMock(ConfigInterface::class);
        $this->config->method('getBuildOutputFolder')->willReturn('/tmp');
        $this->routeService = new RouteService($this->config);
    }

    /**
     * Test simple patterns that should work without embedded parameter logic
     */
    public function testSimplePatternsWorkWithoutEmbeddedLogic(): void
    {
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
        $this->assertEquals(['id' => '123'], $result[1]);
    }
}