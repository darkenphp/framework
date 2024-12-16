<?php

declare(strict_types=1);

namespace Darken\Builder;

class OutputPolyfill implements FileSaveInterface
{
    public function __construct(protected OutputCompiled $compiled)
    {

    }

    public function getNamespace(): string
    {
        return $this->compiled->config->getBuildOutputNamespace() . str_replace(DIRECTORY_SEPARATOR, '\\', $this->compiled->getRelativeDirectory());
    }

    public function getBuildOutputFilePath(): string
    {
        // replace the .php at the end with .polyfill.php
        return str_replace('.compiled.php', '.php', $this->compiled->getBuildOutputFilePath());
    }

    public function getClassName(): string
    {
        $class = pathinfo($this->compiled->input->filePath, PATHINFO_FILENAME);

        // ensure only a-z0-9 are allowed
        return preg_replace('/[^a-zA-Z0-9]/', '', $class);
    }

    public function getBuildOutputContent(): string
    {
        return <<<PHP
            <?php
            namespace {$this->getNamespace()};

            class {$this->getClassName()} extends \Darken\Code\Runtime
            {
                public function renderFilePath(): string
                {
                    return '{$this->compiled->getBuildOutputFilePath()}';
                }
            }
            PHP;
    }
}
