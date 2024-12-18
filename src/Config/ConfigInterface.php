<?php

declare(strict_types=1);

namespace Darken\Config;

interface ConfigInterface
{
    public function getDebugMode(): bool;

    public function getBuildOutputFolder(): string;

    public function getPagesFolder(): string;

    public function getBuildOutputNamespace(): string;

    public function getRootDirectoryPath(): string;

    public function getBuildingFolders(): array;
}
