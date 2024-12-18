<?php

declare(strict_types=1);

namespace Darken\Builder;

use Darken\Config\ConfigInterface;

// rename: CompiledSourceFile

class OutputCompiled implements FileSaveInterface
{
    public function __construct(private string $content, public InputFile $input, public ConfigInterface $config)
    {

    }

    public function getRelativeDirectory(): string
    {
        return str_replace($this->config->getRootDirectoryPath(), '', $this->input->getDirectoryPath());
    }

    public function getFilePath(): string
    {
        return $this->getRelativeDirectory() . DIRECTORY_SEPARATOR . $this->input->getFileName();
    }

    public function getBuildOutputFilePath(): string
    {
        // replace the .php at the end with .polyfill.php
        return str_replace('.php', '.compiled.php', $this->config->getBuildOutputFolder() . $this->getFilePath());
    }

    public function getBuildOutputContent(): string
    {
        return $this->content;
    }
}
