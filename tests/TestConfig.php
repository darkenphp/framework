<?php

namespace Tests;

use Darken\Config\ConfigHelperTrait;
use Darken\Config\ConfigInterface;
use Darken\Service\ContainerService;
use Darken\Service\ContainerServiceInterface;
use Darken\Service\EventService;
use Darken\Service\EventServiceInterface;
use Darken\Service\MiddlewareService;
use Darken\Service\MiddlewareServiceInterface;
use Tests\data\di\Db;

class TestConfig implements ConfigInterface, ContainerServiceInterface, MiddlewareServiceInterface, EventServiceInterface
{
    use ConfigHelperTrait;

    public function __construct(private string $rootDirectoryPath, public string $pagesFolder, public string $builderOutputFolder, public string $componentsFolder)
    {
        $this->loadEnvFile();
    }

    public function setPagesFolder(string $pagesFolder): void
    {
        $this->pagesFolder = $pagesFolder;
    }

    public function setBuilderOutputFolder(string $builderOutputFolder): void
    {
        $this->builderOutputFolder = $builderOutputFolder;
    }

    public function setComponentsFolder(string $componentsFolder): void
    {
        $this->componentsFolder = $componentsFolder;
    }

    public function events(EventService $service): EventService
    {
        return $service->on('testevent', function () {
            return 'test';
        });
    }

    public function containers(ContainerService $service): ContainerService
    {
        return $service->register(new Db('sqlite::memory:'));
    }

    public function middlewares(MiddlewareService $service): MiddlewareService
    {
        return $service;
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
        return false;
    }

    /**
     * ``` 
     * return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->env('DARKEN_BUILD_OUTPUT_FOLDER', '.build');
     * ``` 
     */
    public function getBuildOutputFolder(): string
    {
        return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->builderOutputFolder;
    }

    /**
     * ``` 
     * return $this->env('DARKEN_BUILD_OUTPUT_NAMESPACE', 'Build');
     * ``` 
     */
    public function getBuildOutputNamespace(): string
    {
        return $this->env('DARKEN_BUILD_OUTPUT_NAMESPACE', 'Tests\\Build');
    }

    /**
     * ```
     * return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->env('DARKEN_PAGES_FOLDER', 'pages');
     * ```
     */
    public function getPagesFolder(): string
    {
        return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->pagesFolder;
    }

    public function getComponentsFolder(): string
    {
        return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->componentsFolder;
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
            $this->getComponentsFolder(),
            $this->getPagesFolder(),
        ];
    }
}