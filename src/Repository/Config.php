<?php

declare(strict_types=1);

namespace Darken\Repository;

use DI\Container;
use DI\ContainerBuilder;
use Throwable;
use Yiisoft\Files\FileHelper;

abstract class Config
{
    public function __construct(private readonly string $rootDirectoryPath, private readonly bool $loadDotEnv = true)
    {
        if ($this->loadDotEnv) {
            $this->loadEnvFile();
        }
    }
    
    abstract public function getDebugMode(): bool;

    abstract public function getBuildOutputFolder(): string;

    abstract public function getPagesFolder(): string;

    abstract public function getBuildOutputNamespace(): string;


    public function loadEnvFile(): void
    {
        try {
            $env = @file_get_contents($this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . '.env');
            $lines = explode("\n", $env);

            foreach ($lines as $line) {
                preg_match("/([^#]+)\=(.*)/", $line, $matches);
                if (isset($matches[2])) {
                    putenv(trim($line));
                }
            }
        } catch (Throwable $e) {
            // Do nothing
        }
    }

    public function getRootDirectoryPath(): string
    {
        return FileHelper::normalizePath($this->rootDirectoryPath);
    }

    

    public function getBuildingFolders(): array
    {
        return [
            $this->getPagesFolder(),
            $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . 'components',
        ];
    }

    public function env(string $name, string|bool|int $default = ''): string|bool|int
    {
        return getenv($name) ?: $default;
    }
}
