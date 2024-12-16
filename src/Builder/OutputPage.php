<?php

declare(strict_types=1);

namespace Darken\Builder;

// RENAME: CompiledPage

class OutputPage implements FileSaveInterface
{
    public function __construct(public OutputCompiled $compiled)
    {

    }

    public function getRoute(): string
    {
        $source = str_replace($this->compiled->config->getPagesFolder(), '', $this->compiled->input->filePath);
        // an easy way to convert /blogs/[[slug]] to a matcahable regex like /blogs/<slug:[\w+]>
        return str_replace('.php', '', preg_replace('/\[\[(.*?)\]\]/', '<$1:\w+>', $source));
    }

    public function getSegmentedTrieRoute(): array
    {
        return explode('/', trim($this->getRoute(), '/')); // Split into segments
    }

    public function getClassName()
    {
        return 'Page' . md5($this->getRoute());
    }

    public function getBuildOutputFilePath(): string
    {
        return $this->compiled->config->getBuildOutputFolder() . DIRECTORY_SEPARATOR . $this->getClassName() . '.php';
    }

    public function getFullQulieidNamespacedClassName(): string
    {
        return $this->compiled->config->getBuildOutputNamespace() . '\\' . $this->getClassName();
    }

    public function getBuildOutputContent(): string
    {
        return <<<PHP
            <?php
            namespace {$this->compiled->config->getBuildOutputNamespace()};

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
