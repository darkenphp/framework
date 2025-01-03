<?php

declare(strict_types=1);

namespace Darken\Console\Commands;

use Darken\Builder\CodeCompiler;
use Darken\Builder\CodeCompilerOutput;
use Darken\Builder\InputFile;
use Darken\Builder\OutputCompiled;
use Darken\Builder\OutputPage;
use Darken\Builder\OutputPolyfill;
use Darken\Config\ConfigInterface;
use Darken\Config\PagesConfigInterface;

/**
 * Class FileBuildProcess
 *
 * This class is used to define the build process for a file. It takes an input file
 * and compiles it using the CodeCompiler. It then returns the compiled output and
 * the polyfill output for the file.
 */
class FileBuildProcess
{
    private InputFile $input;

    private CodeCompilerOutput $output;

    public function __construct(string $inputFilePath, private ConfigInterface $config)
    {
        $this->input = new InputFile($inputFilePath);

        $compiler = new CodeCompiler();
        $this->output = $compiler->compile($this->input);
    }

    public function getCompiledOutput(): OutputCompiled
    {
        return new OutputCompiled($this->output->getCode(), $this->input, $this->config);
    }

    public function getPolyfillOutput(): OutputPolyfill
    {
        return new OutputPolyfill($this->getCompiledOutput(), $this->output);
    }

    public function getIsPage(): bool
    {
        return $this->config instanceof PagesConfigInterface && $this->input->isInDirectory($this->config->getPagesFolder());
    }

    public function getPageOutput(PagesConfigInterface $pagesConfig): OutputPage
    {
        return new OutputPage($this->getPolyfillOutput(), $pagesConfig);
    }

    public function getFilesToSaveSequenze(): array
    {
        return [
            $this->getCompiledOutput(),
            $this->getPolyfillOutput(),
        ];
    }
}
