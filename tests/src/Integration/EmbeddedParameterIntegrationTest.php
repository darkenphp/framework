<?php

namespace Tests\src\Integration;

use Darken\Builder\OutputPage;
use Darken\Builder\OutputPolyfill;
use Darken\Builder\OutputCompiled;
use Darken\Builder\InputFile;
use Darken\Builder\CodeCompilerOutput;
use Darken\Builder\Compiler\DataExtractorVisitor;
use Darken\Config\PagesConfigInterface;
use Darken\Service\RouteService;
use Darken\Config\ConfigInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Integration test to demonstrate how the new prefix system fixes the embedded parameter issue
 */
class EmbeddedParameterIntegrationTest extends TestCase
{
    private RouteService $routeService;
    private OutputPage $outputPage;
    private ConfigInterface|MockObject $config;
    private PagesConfigInterface|MockObject $pagesConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = $this->createMock(ConfigInterface::class);
        $this->config->method('getBuildOutputFolder')->willReturn('/tmp');
        
        $this->pagesConfig = $this->createMock(PagesConfigInterface::class);
        $this->pagesConfig->method('getPagesFolder')->willReturn('/app/pages');
        
        $this->routeService = new RouteService($this->config);
    }

    private function createOutputPage(string $filePath): OutputPage
    {
        $polyfill = $this->createMock(OutputPolyfill::class);
        
        $inputFile = $this->createMock(InputFile::class);
        $inputFile->method('getFileName')->willReturn(basename($filePath));
        $inputFile->filePath = $filePath;
        
        $compiled = $this->createMock(OutputCompiled::class);
        $compiled->input = $inputFile;
        
        $dataExtractor = $this->createMock(DataExtractorVisitor::class);
        $dataExtractor->method('onPageDataHook')->willReturn(['methods' => []]);
        
        $compilerOutput = $this->createMock(CodeCompilerOutput::class);
        $compilerOutput->data = $dataExtractor;
        
        $polyfill->compiled = $compiled;
        $polyfill->compilerOutput = $compilerOutput;
        $polyfill->method('getFullQualifiedClassName')->willReturn('Build\\pages\\test');
        
        return new OutputPage($polyfill, $this->pagesConfig);
    }

    private function getRouteFromOutputPage(OutputPage $outputPage): string
    {
        $reflection = new \ReflectionClass($outputPage);
        $getRouteMethod = $reflection->getMethod('getRoute');
        $getRouteMethod->setAccessible(true);
        return $getRouteMethod->invoke($outputPage);
    }

    /**
     * Test the old behavior that caused problems
     */
    public function testOldBehaviorWouldCauseProblems(): void
    {
        // Simulate the old regex pattern with hyphens
        $oldPattern = '/checks/<id:[a-zA-Z0-9\-]+>-<token:[a-zA-Z0-9\-]+>';
        
        // This would have failed because both parameters would match hyphens
        $testString = '38356-123f';
        
        // The first regex [a-zA-Z0-9\-]+ would greedily match the entire string
        $this->assertTrue(preg_match('/^[a-zA-Z0-9\-]+$/', $testString) === 1,
            'Old regex would match entire string greedily');
        
        // Making embedded matching problematic
        $embeddedPattern = '^([a-zA-Z0-9\-]+)-([a-zA-Z0-9\-]+)$';
        $matches = [];
        preg_match('/' . $embeddedPattern . '/', $testString, $matches);
        
        // This would work, but only because of backtracking, which is inefficient
        // and can cause issues with more complex patterns
        $this->assertEquals(['38356-123f', '38356', '123f'], $matches);
    }

    /**
     * Test the new behavior that fixes embedded parameters
     * TODO: Fix trie structure setup for integration testing
     */
    public function testNewBehaviorFixesEmbeddedParameters(): void
    {
        // Create an OutputPage for a file with embedded parameters using 'w' prefix for best results
        $outputPage = $this->createOutputPage('/app/pages/checks/[[w:id]]-[[w:token]].php');
        $route = $this->getRouteFromOutputPage($outputPage);
        
        // The new system generates clean patterns with 'w' prefix
        $this->assertEquals('/checks/<id:[a-zA-Z0-9]+>-<token:[a-zA-Z0-9]+>', $route);
    }

    /**
     * Test complex embedded parameters with different prefixes
     * TODO: Fix trie structure setup for integration testing
     */
    public function testComplexEmbeddedParametersWithPrefixes(): void
    {
        // File: /app/pages/api/[[d:version]]/items/[[w:id]]-[[a:status]]-[[h:hash]].php
        $outputPage = $this->createOutputPage('/app/pages/api/[[d:version]]/items/[[w:id]]-[[a:status]]-[[h:hash]].php');
        $route = $this->getRouteFromOutputPage($outputPage);
        
        $this->assertEquals('/api/<version:[0-9]+>/items/<id:[a-zA-Z0-9]+>-<status:[a-zA-Z]+>-<hash:[a-fA-F0-9]+>', $route);
    }

    /**
     * Test backwards compatibility with simple patterns
     */
    public function testBackwardsCompatibility(): void
    {
        // Old style pattern without prefixes should still work with old default (includes hyphens)
        $outputPage = $this->createOutputPage('/app/pages/users/[[id]].php');
        $route = $this->getRouteFromOutputPage($outputPage);
        
        // Should use old default pattern (includes hyphens for backward compatibility)
        $this->assertEquals('/users/<id:[a-zA-Z0-9-]+>', $route);
        
        $trie = [
            'users' => [
                '_children' => [
                    '<id:[a-zA-Z0-9-]+>' => [
                        '_children' => [
                            'methods' => [
                                '*' => ['class' => 'Build\\pages\\users\\id']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        // Test basic routing functionality
        $result = $this->routeService->findRouteNode('/users/user123');
        $this->assertIsArray($result);
        $this->assertEquals(['id' => 'user123'], $result[1]);
    }    /**
     * Test that string prefix [[s:name]] maintains old behavior when needed
     * TODO: Fix trie structure setup for integration testing
     */
    public function testStringPrefixMaintainsOldBehavior(): void
    {
        // Use string prefix to explicitly allow hyphens when needed
        $outputPage = $this->createOutputPage('/app/pages/slugs/[[s:slug]].php');
        $route = $this->getRouteFromOutputPage($outputPage);
        
        // Should still use default pattern with hyphens for s: prefix
        $this->assertEquals('/slugs/<slug:[a-zA-Z0-9-]+>', $route);
    }

    /**
     * Test URL generation with the new route service
     */
    public function testUrlGenerationWithPrefixes(): void
    {
        // Start with a simpler test case
        $trie = [
            'users' => [
                '_children' => [
                    'methods' => [
                        'GET' => ['class' => 'Build\\pages\\users']
                    ]
                ],
                '<id:[a-zA-Z0-9]+>' => [
                    '_children' => [
                        'methods' => [
                            'GET' => ['class' => 'Build\\pages\\users\\id']
                        ]
                    ]
                ]
            ]
        ];
        
        $this->routeService->setTrieForTesting($trie);
        
        // Test URL generation
        $url = $this->routeService->url('Build\\pages\\users\\id', [
            'id' => 'user123'
        ]);
        
        $this->assertEquals('/users/user123', $url);
        
        // Test parameter validation
        try {
            $this->routeService->url('Build\\pages\\users\\id', [
                'id' => 'user-123'  // Invalid: should not contain hyphens with new default
            ]);
            $this->fail('Should have thrown exception for invalid id parameter');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString("Param 'id' value 'user-123' does not match /[a-zA-Z0-9]+/", $e->getMessage());
        }
    }
}