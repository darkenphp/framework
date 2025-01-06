<?php

declare(strict_types=1);

namespace Darken\Builder;

use Darken\Config\ConfigInterface;
use Yiisoft\Files\FileHelper;

class OutputCompiled implements FileSaveInterface
{
    public function __construct(private string $content, public InputFile $input, public ConfigInterface $config)
    {

    }

    public function getRelativeDirectory(): string
    {
        $path = str_replace($this->config->getRootDirectoryPath(), '', $this->input->getDirectoryPath());

        return  preg_replace('/[^a-zA-Z0-9\/_-]/', '', FileHelper::normalizePath($path));
    }

    // save interface

    public function getBuildOutputFilePath(): string
    {
        // replace the .php at the end with .polyfill.php
        return str_replace('.php', '.compiled.php', $this->config->getBuildOutputFolder() . $this->getFilePath());
    }

    public function getBuildOutputContent(): string
    {
        return $this->content;
    }

    private function getFilePath(): string
    {
        return $this->getRelativeDirectory() . DIRECTORY_SEPARATOR . $this->input->getEnsuredFileName();
    }
}
