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
        $fileName = basename($this->filePath);

        return preg_replace('/[^a-zA-Z0-9]/', '', str_replace('.php', '', $fileName)) . '.php';
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
