<?php

namespace Tests\src\Builder;

use Darken\Builder\OutputPage;
use Darken\Builder\OutputPolyfill;
use Darken\Builder\OutputCompiled;
use Darken\Builder\InputFile;
use Darken\Builder\CodeCompilerOutput;
use Darken\Builder\Compiler\DataExtractorVisitor;
use Darken\Config\PagesConfigInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class OutputPagePrefixTest extends TestCase
{
    private OutputPage $outputPage;
    private OutputPolyfill|MockObject $polyfill;
    private PagesConfigInterface|MockObject $pagesConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->polyfill = $this->createMock(OutputPolyfill::class);
        $this->pagesConfig = $this->createMock(PagesConfigInterface::class);
        $this->outputPage = new OutputPage($this->polyfill, $this->pagesConfig);
    }

    private function setupMocks(string $filePath): void
    {
        $inputFile = $this->createMock(InputFile::class);
        $inputFile->method('getFileName')->willReturn(basename($filePath));
        $inputFile->filePath = $filePath;
        
        $compiled = $this->createMock(OutputCompiled::class);
        $compiled->input = $inputFile;
        
        $dataExtractor = $this->createMock(DataExtractorVisitor::class);
        $dataExtractor->method('onPageDataHook')->willReturn(['methods' => []]);
        
        $compilerOutput = $this->createMock(CodeCompilerOutput::class);
        $compilerOutput->data = $dataExtractor;
        
        $this->polyfill->compiled = $compiled;
        $this->polyfill->compilerOutput = $compilerOutput;
        $this->polyfill->method('getFullQualifiedClassName')->willReturn('Build\\pages\\test');
        $this->pagesConfig->method('getPagesFolder')->willReturn('/app/pages');
    }

    private function getRoute(): string
    {
        $reflection = new \ReflectionClass($this->outputPage);
        $getRouteMethod = $reflection->getMethod('getRoute');
        $getRouteMethod->setAccessible(true);
        return $getRouteMethod->invoke($this->outputPage);
    }

    /**
     * Test the default behavior maintains backward compatibility
     */
    public function testDefaultRegexMaintainsBackwardCompatibility(): void
    {
        $this->setupMocks('/app/pages/users/[[id]].php');
        $route = $this->getRoute();
        
        // Default should maintain backward compatibility (includes hyphens)
        $this->assertEquals('/users/<id:[a-zA-Z0-9-]+>', $route);
    }

    /**
     * Test that embedded parameters work correctly with prefixed patterns
     */
    public function testEmbeddedParametersWithPrefixedPatterns(): void
    {
        $this->setupMocks('/app/pages/checks/[[w:id]]-[[w:token]].php');
        $route = $this->getRoute();
        
        // Should generate pattern that works with embedded parameters using 'w' prefix
        $this->assertEquals('/checks/<id:[a-zA-Z0-9]+>-<token:[a-zA-Z0-9]+>', $route);
        
        // Verify that the embedded parameters would work correctly
        $pattern = 'checks/([a-zA-Z0-9]+)-([a-zA-Z0-9]+)';
        $this->assertTrue(preg_match('#^' . $pattern . '$#', 'checks/38356-123f', $matches) === 1);
        $this->assertEquals(['checks/38356-123f', '38356', '123f'], $matches);
    }

    /**
     * Test digit prefix [[d:name]]
     */
    public function testDigitPrefix(): void
    {
        $this->setupMocks('/app/pages/users/[[d:id]].php');
        $route = $this->getRoute();
        
        $this->assertEquals('/users/<id:[0-9]+>', $route);
    }

    /**
     * Test word prefix [[w:name]] - explicit word characters
     */
    public function testWordPrefix(): void
    {
        $this->setupMocks('/app/pages/users/[[w:username]].php');
        $route = $this->getRoute();
        
        $this->assertEquals('/users/<username:[a-zA-Z0-9]+>', $route);
    }

    /**
     * Test string prefix [[s:name]] - includes hyphens (old behavior)
     */
    public function testStringPrefix(): void
    {
        $this->setupMocks('/app/pages/slugs/[[s:slug]].php');
        $route = $this->getRoute();
        
        $this->assertEquals('/slugs/<slug:[a-zA-Z0-9-]+>', $route);
    }

    /**
     * Test alpha prefix [[a:name]] - letters only
     */
    public function testAlphaPrefix(): void
    {
        $this->setupMocks('/app/pages/categories/[[a:category]].php');
        $route = $this->getRoute();
        
        $this->assertEquals('/categories/<category:[a-zA-Z]+>', $route);
    }

    /**
     * Test hex prefix [[h:name]] - hexadecimal characters
     */
    public function testHexPrefix(): void
    {
        $this->setupMocks('/app/pages/hashes/[[h:hash]].php');
        $route = $this->getRoute();
        
        $this->assertEquals('/hashes/<hash:[a-fA-F0-9]+>', $route);
    }

    /**
     * Test mixed prefixes in the same route
     */
    public function testMixedPrefixes(): void
    {
        $this->setupMocks('/app/pages/api/[[d:version]]/users/[[w:username]]/posts/[[h:hash]].php');
        $route = $this->getRoute();
        
        $this->assertEquals('/api/<version:[0-9]+>/users/<username:[a-zA-Z0-9]+>/posts/<hash:[a-fA-F0-9]+>', $route);
    }

    /**
     * Test complex embedded parameters with different prefixes
     */
    public function testComplexEmbeddedParameters(): void
    {
        $this->setupMocks('/app/pages/items/[[d:id]]-[[a:status]]-[[w:name]].php');
        $route = $this->getRoute();
        
        $this->assertEquals('/items/<id:[0-9]+>-<status:[a-zA-Z]+>-<name:[a-zA-Z0-9]+>', $route);
        
        // Verify that this would work correctly for matching
        $pattern = 'items/([0-9]+)-([a-zA-Z]+)-([a-zA-Z0-9]+)';
        $this->assertTrue(preg_match('#^' . $pattern . '$#', 'items/123-active-item1', $matches) === 1);
        $this->assertEquals(['items/123-active-item1', '123', 'active', 'item1'], $matches);
    }

    /**
     * Test catch-all patterns still work
     */
    public function testCatchAllPatterns(): void
    {
        $this->setupMocks('/app/pages/files/[[...path]].php');
        $route = $this->getRoute();
        
        $this->assertEquals('/files/<path:.+>', $route);
    }

    /**
     * Test backwards compatibility - parameters without colons should use old default
     */
    public function testBackwardsCompatibility(): void
    {
        $this->setupMocks('/app/pages/legacy/[[id]].php');
        $route = $this->getRoute();
        
        // Should use old default (includes hyphens for backward compatibility)
        $this->assertEquals('/legacy/<id:[a-zA-Z0-9-]+>', $route);
    }

    /**
     * Test invalid prefix defaults to word characters
     */
    public function testInvalidPrefixDefaultsToWord(): void
    {
        // Test with reflection to access the private method
        $reflection = new \ReflectionClass($this->outputPage);
        $getRegexMethod = $reflection->getMethod('getRegexForPrefix');
        $getRegexMethod->setAccessible(true);
        
        $this->assertEquals('[a-zA-Z0-9]+', $getRegexMethod->invoke($this->outputPage, 'w'));
        $this->assertEquals('[a-zA-Z0-9-]+', $getRegexMethod->invoke($this->outputPage, ''));
        $this->assertEquals('[a-zA-Z0-9-]+', $getRegexMethod->invoke($this->outputPage, 'invalid'));
    }

    /**
     * Test that method suffixes still work with prefixes
     */
    public function testMethodSuffixesWithPrefixes(): void
    {
        $this->setupMocks('/app/pages/api/users/[[d:id]].get.php');
        $route = $this->getRoute();
        
        $this->assertEquals('/api/users/<id:[0-9]+>', $route);
    }

    /**
     * Test edge case: parameter name contains colon
     */
    public function testParameterNameWithColon(): void
    {
        $this->setupMocks('/app/pages/namespaced/[[namespace:class]].php');
        $route = $this->getRoute();
        
        // Should treat "namespace:class" as the parameter name with default regex (backward compatibility)
        $this->assertEquals('/namespaced/<namespace:class:[a-zA-Z0-9-]+>', $route);
    }

    /**
     * Test all prefix types work correctly with real regex validation
     */
    public function testAllPrefixRegexValidation(): void
    {
        $testCases = [
            ['d', '[0-9]+', '123', true],
            ['d', '[0-9]+', 'abc', false],
            ['w', '[a-zA-Z0-9]+', 'abc123', true],
            ['w', '[a-zA-Z0-9]+', 'abc-123', false],
            ['s', '[a-zA-Z0-9-]+', 'abc-123', true],
            ['s', '[a-zA-Z0-9-]+', 'abc_123', false],
            ['a', '[a-zA-Z]+', 'abc', true],
            ['a', '[a-zA-Z]+', 'abc123', false],
            ['h', '[a-fA-F0-9]+', 'DEADBEEF', true],
            ['h', '[a-fA-F0-9]+', 'xyz', false],
        ];

        $reflection = new \ReflectionClass($this->outputPage);
        $getRegexMethod = $reflection->getMethod('getRegexForPrefix');
        $getRegexMethod->setAccessible(true);

        foreach ($testCases as [$prefix, $expectedRegex, $testString, $shouldMatch]) {
            $actualRegex = $getRegexMethod->invoke($this->outputPage, $prefix);
            $this->assertEquals($expectedRegex, $actualRegex, "Regex for prefix '{$prefix}' should be '{$expectedRegex}'");
            
            $matches = preg_match('/^' . $actualRegex . '$/', $testString);
            if ($shouldMatch) {
                $this->assertEquals(1, $matches, "String '{$testString}' should match regex '{$actualRegex}' for prefix '{$prefix}'");
            } else {
                $this->assertEquals(0, $matches, "String '{$testString}' should NOT match regex '{$actualRegex}' for prefix '{$prefix}'");
            }
        }
    }
}