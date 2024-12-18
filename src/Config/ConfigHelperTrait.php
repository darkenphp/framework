<?php

declare(strict_types=1);

namespace Darken\Config;

use Throwable;
use Yiisoft\Files\FileHelper;

trait ConfigHelperTrait
{
    /**
     * - Convert all directory separators into `/` (e.g. "\a/b\c" becomes "/a/b/c")
     * - Remove trailing directory separators (e.g. "/a/b/c/" becomes "/a/b/c")
     * - Turn multiple consecutive slashes into a single one (e.g. "/a///b/c" becomes "/a/b/c")
     * - Remove ".." and "." based on their meanings (e.g. "/a/./b/../c" becomes "/a/c")
     */
    public function path(string $path): string
    {
        return FileHelper::normalizePath($path);
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

    public function env(string $name, string|bool|int $default = ''): string|bool|int
    {
        return getenv($name) ?: $default;
    }
}
