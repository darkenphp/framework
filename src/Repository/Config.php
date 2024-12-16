<?php

declare(strict_types=1);

namespace Darken\Repository;

use Throwable;
use Yiisoft\Files\FileHelper;

class Config
{
    public function __construct(private readonly string $rootDirectoryPath, private readonly bool $loadDotEnv = true)
    {
        if ($this->loadDotEnv) {
            $this->loadEnvFile();
        }
    }

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

    public function getDebugMode(): bool
    {
        return (bool) self::env('DARKEN_DEBUG', false);
    }

    public function getBuildOutputFolder(): string
    {
        return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . self::env('DARKEN_BUILD_OUTPUT_FOLDER', '.build');
    }

    public function getPagesFolder(): string
    {
        return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . self::env('DARKEN_PAGES_FOLDER', 'pages');
    }

    public function getBuildOutputNamespace(): string
    {
        return self::env('DARKEN_BUILD_OUTPUT_NAMESPACE', 'Build');
    }

    public function getBuildingFolders(): array
    {
        return [
            $this->getPagesFolder(),
            $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . 'components',
        ];
    }

    public static function env(string $name, string|bool|int $default = ''): string|bool|int
    {
        return getenv($name) ?: $default;
    }
}
