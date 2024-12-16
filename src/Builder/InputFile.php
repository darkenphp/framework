<?php

declare(strict_types=1);

namespace Darken\Builder;

class InputFile
{
    public function __construct(public string $filePath)
    {

    }

    public function getFileName(): string
    {
        return basename($this->filePath);
    }

    public function getDirectoryPath(): string
    {
        return dirname($this->filePath);
    }

    public function isInDirectory(string $directory): bool
    {
        return strpos($this->filePath, $directory) === 0;
    }

    public function getContent(): string
    {
        return file_get_contents($this->filePath);
    }
}
