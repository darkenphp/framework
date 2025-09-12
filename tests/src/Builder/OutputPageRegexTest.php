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

class OutputPageRegexTest extends TestCase
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

    /**
     * Test the default regex pattern maintains backward compatibility
     * But the prefix system provides a solution for embedded parameters
     */
    public function testDefaultRegexMaintainsBackwardCompatibility(): void
    {
        // Create proper mocks
        $inputFile = $this->createMock(InputFile::class);
        $inputFile->method('getFileName')->willReturn('[[id]].php');
        $inputFile->filePath = '/app/pages/users/[[id]].php';
        
        $compiled = $this->createMock(OutputCompiled::class);
        $compiled->input = $inputFile;
        
        $dataExtractor = $this->createMock(DataExtractorVisitor::class);
        $dataExtractor->method('onPageDataHook')->willReturn(['methods' => []]);
        
        $compilerOutput = $this->createMock(CodeCompilerOutput::class);
        $compilerOutput->data = $dataExtractor;
        
        $this->polyfill->compiled = $compiled;
        $this->polyfill->compilerOutput = $compilerOutput;
        $this->polyfill->method('getFullQualifiedClassName')->willReturn('Build\\pages\\users\\id');
        $this->pagesConfig->method('getPagesFolder')->willReturn('/app/pages');

        // Use reflection to access the private getRoute method
        $reflection = new \ReflectionClass($this->outputPage);
        $getRouteMethod = $reflection->getMethod('getRoute');
        $getRouteMethod->setAccessible(true);

        $route = $getRouteMethod->invoke($this->outputPage);
        
        // Default behavior should remain: /users/<id:[a-zA-Z0-9-]+>
        $this->assertEquals('/users/<id:[a-zA-Z0-9-]+>', $route);
    }

    /**
     * Test that the prefix system provides a solution for embedded parameters
     */
    public function testPrefixSystemSolvesEmbeddedParameterProblems(): void
    {
        // Create proper mocks for prefixed embedded parameters
        $inputFile = $this->createMock(InputFile::class);
        $inputFile->method('getFileName')->willReturn('[[w:id]]-[[w:token]].php');
        $inputFile->filePath = '/app/pages/checks/[[w:id]]-[[w:token]].php';
        
        $compiled = $this->createMock(OutputCompiled::class);
        $compiled->input = $inputFile;
        
        $dataExtractor = $this->createMock(DataExtractorVisitor::class);
        $dataExtractor->method('onPageDataHook')->willReturn(['methods' => []]);
        
        $compilerOutput = $this->createMock(CodeCompilerOutput::class);
        $compilerOutput->data = $dataExtractor;
        
        $this->polyfill->compiled = $compiled;
        $this->polyfill->compilerOutput = $compilerOutput;
        $this->polyfill->method('getFullQualifiedClassName')->willReturn('Build\\pages\\checks\\idtoken');
        $this->pagesConfig->method('getPagesFolder')->willReturn('/app/pages');

        // Use reflection to access the private getRoute method
        $reflection = new \ReflectionClass($this->outputPage);
        $getRouteMethod = $reflection->getMethod('getRoute');
        $getRouteMethod->setAccessible(true);

        $route = $getRouteMethod->invoke($this->outputPage);
        
        // Prefixed implementation produces: /checks/<id:[a-zA-Z0-9]+>-<token:[a-zA-Z0-9]+>
        $this->assertEquals('/checks/<id:[a-zA-Z0-9]+>-<token:[a-zA-Z0-9]+>', $route);
        
        // The 'w' prefix regex [a-zA-Z0-9]+ (without hyphens) fixes the embedded parameter issue
        $this->assertFalse(preg_match('/^[a-zA-Z0-9]+$/', '38356-123f') === 1, 
            'Prefix w: regex should NOT match the entire string including separator');
        
        // But individual parts should match correctly
        $this->assertTrue(preg_match('/^[a-zA-Z0-9]+$/', '38356') === 1, 
            'Prefix w: regex should match the first part');
        $this->assertTrue(preg_match('/^[a-zA-Z0-9]+$/', '123f') === 1, 
            'Prefix w: regex should match the second part');
    }

    /**
     * Test what happens if we remove hyphens from the default regex
     * This should make embedded parameters work correctly
     */
    public function testRegexWithoutHyphensWouldFixEmbeddedParameters(): void
    {
        // If we used [a-zA-Z0-9]+ instead of [a-zA-Z0-9\-]+
        $regexWithoutHyphens = '[a-zA-Z0-9]+';
        
        // Test that it doesn't match the separator
        $this->assertTrue(preg_match('/^' . $regexWithoutHyphens . '$/', '38356') === 1,
            'Regex without hyphens should match the ID part');
        $this->assertTrue(preg_match('/^' . $regexWithoutHyphens . '$/', '123f') === 1,
            'Regex without hyphens should match the token part');
        $this->assertFalse(preg_match('/^' . $regexWithoutHyphens . '$/', '38356-123f') === 1,
            'Regex without hyphens should NOT match the entire string with separator');
        
        // Test embedded pattern matching
        $pattern = '^(' . $regexWithoutHyphens . ')-(' . $regexWithoutHyphens . ')$';
        $this->assertTrue(preg_match('/' . $pattern . '/', '38356-123f', $matches) === 1,
            'Embedded pattern should work correctly');
        $this->assertEquals(['38356-123f', '38356', '123f'], $matches,
            'Should extract both parameters correctly');
    }

    /**
     * Test various scenarios where hyphens in filenames are legitimate
     */
    public function testLegitimateHyphenUseCases(): void
    {
        // Case 1: Hyphen in parameter name itself (should still work)
        $this->assertTrue(preg_match('/^[a-zA-Z0-9]+$/', 'user123') === 1);
        $this->assertTrue(preg_match('/^[a-zA-Z0-9]+$/', 'abc') === 1);
        
        // Case 2: When we actually want to allow hyphens in a parameter
        // We could use a specific prefix for that
        $this->assertTrue(preg_match('/^[a-zA-Z0-9\-]+$/', 'user-name-123') === 1);
    }

    /**
     * Test what different regex patterns would look like
     */
    public function testDifferentRegexPatterns(): void
    {
        $patterns = [
            'd' => '[0-9]+',           // digits only
            'w' => '[a-zA-Z0-9]+',     // word characters (no hyphens)
            's' => '[a-zA-Z0-9\-]+',   // string with hyphens (current default)
            'a' => '[a-zA-Z]+',        // letters only
            'h' => '[a-fA-F0-9]+',     // hex characters
        ];

        foreach ($patterns as $prefix => $regex) {
            // Test that each pattern works as expected
            switch ($prefix) {
                case 'd':
                    $this->assertTrue(preg_match('/^' . $regex . '$/', '123') === 1);
                    $this->assertFalse(preg_match('/^' . $regex . '$/', 'abc') === 1);
                    break;
                case 'w':
                    $this->assertTrue(preg_match('/^' . $regex . '$/', 'abc123') === 1);
                    $this->assertFalse(preg_match('/^' . $regex . '$/', 'abc-123') === 1);
                    break;
                case 's':
                    $this->assertTrue(preg_match('/^' . $regex . '$/', 'abc-123') === 1);
                    break;
                case 'a':
                    $this->assertTrue(preg_match('/^' . $regex . '$/', 'abc') === 1);
                    $this->assertFalse(preg_match('/^' . $regex . '$/', 'abc123') === 1);
                    break;
                case 'h':
                    $this->assertTrue(preg_match('/^' . $regex . '$/', 'abc123') === 1);
                    $this->assertTrue(preg_match('/^' . $regex . '$/', 'DEADBEEF') === 1);
                    $this->assertFalse(preg_match('/^' . $regex . '$/', 'xyz') === 1);
                    break;
            }
        }
    }
}