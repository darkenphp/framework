<?php

namespace App;

use Darken\Config\ConfigHelperTrait;
use Darken\Config\ConfigInterface;
use Darken\Enum\MiddlewarePosition;
use Darken\Service\MiddlewareService;
use Darken\Service\MiddlewareServiceInterface;
use Darken\Middleware\AddCustomHeaderMiddleware;
use Darken\Service\ContainerSericeInterface;
use Darken\Service\ContainerService;

class Config implements ConfigInterface, MiddlewareServiceInterface, ContainerSericeInterface
{
    use ConfigHelperTrait;

    public function containers(ContainerService $service): ContainerService
    {
        return $service->register(new Test($this->env('DARKEN_TEST', 'test')));   
    }

    public function middlewares(MiddlewareService $service): MiddlewareService
    {
        return $service->add(new AddCustomHeaderMiddleware('Authorization', 'test'), MiddlewarePosition::BEFORE);
    }

    public function __construct(private readonly string $rootDirectoryPath)
    {
        $this->loadEnvFile();
    }

    public function getRootDirectoryPath(): string
    {
        return $this->path($this->rootDirectoryPath);
    }

    public function getDebugMode(): bool
    {
        return (bool) $this->env('DARKEN_DEBUG', false);
    }

    public function getPagesFolder(): string
    {
        return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->env('DARKEN_PAGES_FOLDER', 'pages');
    }

    public function getBuildOutputFolder(): string
    {
        return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->env('DARKEN_BUILD_OUTPUT_FOLDER', '.build');
    }

    public function getBuildOutputNamespace(): string
    {
        return $this->env('DARKEN_BUILD_OUTPUT_NAMESPACE', 'Build');
    }

    public function getBuildingFolders(): array
    {
        return [
            $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . 'components',
            $this->getPagesFolder(),
        ];
    }
}