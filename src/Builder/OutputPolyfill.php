<?php

declare(strict_types=1);

namespace Darken\Builder;

use Darken\Builder\Polyfill\Creator;
use PhpParser\PrettyPrinter\Standard;

class OutputPolyfill implements FileSaveInterface
{
    public function __construct(public OutputCompiled $compiled, public CodeCompilerOutput $compilerOutput)
    {

    }

    public function getFullQualifiedClassName(): string
    {
        return $this->getNamespace() . '\\' . $this->getClassName();
    }

    // save interface

    public function getBuildOutputFilePath(): string
    {
        // replace the .php at the end with .polyfill.php
        return str_replace('.compiled.php', '.php', $this->compiled->getBuildOutputFilePath());
    }

    public function getBuildOutputContent(): string
    {
        $creator = new Creator();
        $node = $creator->createNode($this);

        $prettyPrinter = new Standard();
        $ast = [$node];
        return CodeCompiler::doNotModifyHint(false).$prettyPrinter->prettyPrintFile($ast);
    }

    public function getClassName(): string
    {
        $class = pathinfo($this->compiled->input->filePath, PATHINFO_FILENAME);

        // ensure only a-z0-9 are allowed
        return preg_replace('/[^a-zA-Z0-9]/', '', $class);
    }

    public function getNamespace(): string
    {
        $directory = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $this->compiled->getRelativeDirectory());
        return $this->compiled->config->getBuildOutputNamespace() . str_replace(DIRECTORY_SEPARATOR, '\\', $directory);
    }

    public function getRelativeBuildOutputFilePath(): string
    {
        return str_replace('.php', '.compiled.php', $this->compiled->input->getFileName());
    }
}
