<?php

namespace Tests;

use Darken\Config\ConfigHelperTrait;
use Darken\Config\ConfigInterface;

class TestConfig implements ConfigInterface
{
    use ConfigHelperTrait;

    public function __construct(private readonly string $rootDirectoryPath)
    {
        $this->loadEnvFile();
    }

        /**
     * ```
     * return $this->path($this->rootDirectoryPath);
     * ```
     */
    public function getRootDirectoryPath(): string
    {
        return $this->path($this->rootDirectoryPath);
    }

    /**
     * ```
     * return (bool) $this->env('DARKEN_DEBUG', false);
     * ```
     */
    public function getDebugMode(): bool
    {
        return (bool) $this->env('DARKEN_DEBUG', false);
    }

    /**
     * ``` 
     * return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->env('DARKEN_BUILD_OUTPUT_FOLDER', '.build');
     * ``` 
     */
    public function getBuildOutputFolder(): string
    {
        return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->env('DARKEN_BUILD_OUTPUT_FOLDER', '.build');
    }

    /**
     * ``` 
     * return $this->env('DARKEN_BUILD_OUTPUT_NAMESPACE', 'Build');
     * ``` 
     */
    public function getBuildOutputNamespace(): string
    {
        return $this->env('DARKEN_BUILD_OUTPUT_NAMESPACE', 'Build');
    }

    /**
     * ```
     * return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->env('DARKEN_PAGES_FOLDER', 'pages');
     * ```
     */
    public function getPagesFolder(): string
    {
        return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->env('DARKEN_PAGES_FOLDER', 'pages');
    }

    /**
     * ```
     * return [
     *     $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . 'components',
     *     $this->getPagesFolder(),
     * ];
     * ```
     */
    public function getBuildingFolders(): array
    {
        return [
            $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . 'components',
            $this->getPagesFolder(),
        ];
    }
}