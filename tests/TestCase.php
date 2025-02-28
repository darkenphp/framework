<?php

declare(strict_types=1);

namespace Tests;

error_reporting(E_ALL);
ini_set('display_errors', '1');

use Darken\Builder\CodeCompiler;
use Darken\Builder\InputFile;
use Darken\Builder\OutputCompiled;
use Darken\Builder\OutputPolyfill;
use Darken\Code\Runtime;
use Darken\Config\ConfigInterface;
use Darken\Console\Commands\Build;
use Darken\Builder\FileBuildProcess;
use Darken\Web\Application;
use Darken\Web\Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PHPUnit\Framework\TestCase as FrameworkTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Yiisoft\Files\FileHelper;

class TestCase extends FrameworkTestCase
{
    public function getTestsRootFolder(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests';
    }

    public function createServerRequest(string $uri, string $method): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();
        $serverRequestFactory = new class implements ServerRequestFactoryInterface
        {
            public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
            {
                return new Request($method, $uri, [], null, '1.1', $serverParams);
            }
        };
        $creator = new ServerRequestCreator(
            $serverRequestFactory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        // Parse the URI to extract components
        $parsedUrl = parse_url($uri);

        // Define server parameters
        $server = [
            'REQUEST_METHOD'  => strtoupper($method),
            'REQUEST_URI'     => $uri,
            'QUERY_STRING'    => $parsedUrl['query'] ?? '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST'       => 'localhost',
            'HTTPS'           => 'off', // Adjust as needed
            // Add other necessary server parameters if required
        ];

        // Define query parameters separately
        $query = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
        }

        // Define other parameters (cookies, files, body)
        $cookies = []; // Add mock cookies if needed
        $files = [];   // Add mock files if needed
        $body = null;  // For GET requests, body is typically null or empty

        // Create the ServerRequest instance with separate arrays
        $request = $creator->fromArrays($server, [], $cookies, $query, null, $files, $body);

        return $request;
    }

    public function createConfig(): TestConfig
    {
        return new TestConfig(
            rootDirectoryPath: $this->getTestsRootFolder(),
            pagesFolder: 'data/pages',
            componentsFolder: 'data/components',
            builderOutputFolder: '.build',
        );
    }

    public function clear()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public function tmpFile(string $path, string $content) : string
    {
        try {
            FileHelper::unlink($path);
        } catch (\Throwable $e) {
            // remove all registered php error handler
            restore_error_handler();
            restore_exception_handler();
        }
        file_put_contents($path, $content);
        
        return $path;
    }

    public function createTmpFile(ConfigInterface $config, string $fileName, string $content): string
    {
        $folder = FileHelper::normalizePath($config->getRootDirectoryPath() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'generated');

        FileHelper::ensureDirectory($folder);

        $fullPath = $folder . DIRECTORY_SEPARATOR . $fileName;

        return $this->tmpFile($fullPath, $content);
    }

    public function destoryTmpFile(string $path): void
    {
        try {
        FileHelper::unlink($path);
        } catch (\Throwable $e) {
            restore_error_handler();
        restore_exception_handler();
        }
    }

    public function createInputFile($path): InputFile
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('File for testing does not exist: ' . $path);
        }
        return new InputFile($path);
    }

    public function createCompileTest(TestConfig $config, string $content, string $expecedCompiledCode, string $expectedPolyfillCode): string
    {
        $tmpFile = $this->createTmpFile($config, 'test.php', $content);

        $file = $this->createInputFile($tmpFile);

        $compiler = new CodeCompiler();
        $output = $compiler->compile($file);

        $this->assertSame($expecedCompiledCode, $output->getCode(), 'Compiled code does not match');

        $outputCompiled = new OutputCompiled($output->getCode(), $file, $config);

        $polyfill = new OutputPolyfill($outputCompiled, $output);

        $this->assertSame($expectedPolyfillCode, $polyfill->getBuildOutputContent(), 'Polyfill code does not match');

        $process = new  FileBuildProcess($tmpFile, $config);

        $files = $process->getFilesToSaveSequenze();
        foreach ($files as $file) {
            $this->assertTrue(Build::createFile($file));
        }

        // get namespace
        return $polyfill->getFullQualifiedClassName();
    }

    public function mockWebAppRequest(TestConfig $config, string $verb, string $uri): ResponseInterface
    {
        $web = new Application($config);

        restore_error_handler();
        restore_exception_handler();

        $reflection = new ReflectionClass($web);
        $method = $reflection->getMethod('handleServerRequest');
        $method->setAccessible(true);

        $rqst = $this->createServerRequest($uri, $verb);

        return $method->invoke($web, $rqst);
    }

    public function createRuntime($class, array $params = []): Runtime
    {
        return new $class(...$params);
    }
}
