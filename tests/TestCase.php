<?php declare(strict_types=1);

namespace Tests;

error_reporting(E_ALL);
ini_set('display_errors', '1');

use Darken\Builder\InputFile;
use Darken\Config\ConfigInterface;
use PHPUnit\Framework\TestCase as FrameworkTestCase;

class TestCase extends FrameworkTestCase
{
    public function createConfig() : ConfigInterface
    {
        return new TestConfig(
            rootDirectoryPath: dirname(__DIR__)
        );
    }

    public function createInputFile($path) : InputFile
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('File for testing does not exist: ' . $path);
        }
        return new InputFile($path);
    }
}