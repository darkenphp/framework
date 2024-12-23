<?php declare(strict_types=1);

namespace Tests;

error_reporting(E_ALL);
ini_set('display_errors', '1');

use Darken\Builder\InputFile;
use Darken\Config\ConfigInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PHPUnit\Framework\TestCase as FrameworkTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Files\FileHelper;

class TestCase extends FrameworkTestCase
{
    public function getTestsRootFolder() : string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests';
    }

    public function createServerRequest(string $uri, string $method) : ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );
        $request = $creator->fromArrays([
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI' => $uri,
        ]);

        return $request;
    }

    public function createConfig() : TestConfig
    {
        return new TestConfig(
            rootDirectoryPath: $this->getTestsRootFolder(),
            pagesFolder: 'data/pages',
            builderOutputFolder: '.build',
            componentsFolder: 'data/components'
        );
    }

    public function createTmpFile(string $fileName, string $content) : string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
        file_put_contents($path, $content);
        return $path;
    }

    public function destoryTmpFile(string $path) : void
    {
        FileHelper::unlink($path);
    }

    public function createInputFile($path) : InputFile
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('File for testing does not exist: ' . $path);
        }
        return new InputFile($path);
    }
}