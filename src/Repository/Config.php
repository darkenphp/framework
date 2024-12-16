<?php

declare(strict_types=1);

namespace Darken\Repository;

use Yiisoft\Files\FileHelper;

class Config
{
    public function __construct(private readonly string $rootDirectoryPath)
    {

    }

    public function getRootDirectoryPath(): string
    {
        return FileHelper::normalizePath($this->rootDirectoryPath);
    }

    public function getBuildOutputFolder(): string
    {
        return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . '.build';
    }

    public function getPagesFolder(): string
    {
        return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . 'pages';
    }

    public function getBuildOutputNamespace(): string
    {
        return 'Build';
    }

    public function getBuildingFolders(): array
    {
        return [
            $this->getPagesFolder(),
            $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . 'components',
        ];
    }
}
