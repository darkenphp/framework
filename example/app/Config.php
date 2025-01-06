<?php
namespace App;
use Darken\Config\BaseConfig;
use Darken\Config\ConfigHelperTrait;
use Darken\Config\ConfigInterface;
use Darken\Enum\MiddlewarePosition;
use Darken\Service\MiddlewareService;
use Darken\Service\MiddlewareServiceInterface;
use Darken\Middleware\AddCustomHeaderMiddleware;
use Darken\Service\ContainerServiceInterface;
use Darken\Service\ContainerService;
class Config extends BaseConfig 
{
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
        return true;
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