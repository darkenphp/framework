<?php

declare(strict_types=1);

namespace Darken\Console;

use Darken\Console\Commands\Build;
use Darken\Console\Commands\Dev;
use Darken\Console\Commands\Watch;
use Darken\Kernel;

class Application extends Kernel
{
    // ANSI color codes
    private const COLOR_RED = "\033[31m";

    private const COLOR_YELLOW = "\033[33m";

    private const COLOR_GREEN = "\033[32m";

    private const COLOR_RESET = "\033[0m";

    public function initalize(): void
    {
        if ($this->config->getDebugMode()) {
            $this->whoops->pushHandler(new \Whoops\Handler\PlainTextHandler());
            $this->whoops->register();
        }
    }

    public function run(): void
    {
        switch ($this->getCommand()) {
            case 'build':
                $build = new Build();
                $build->clear = $this->getArgument('clear', false);
                $build->run($this);
                break;
            case 'dev':
                $build = new Dev();
                $build->run($this);
                break;
            case 'watch':
                $build = new Watch();
                $build->run($this);
                break;
        }
    }

    public function getBin(): string
    {
        return $_SERVER['argv'][0];
    }

    public function getCommand(): ?string
    {
        return $_SERVER['argv'][1] ?? null;
    }

    public function getArguments(): array
    {
        $opts = [];
        foreach (array_slice($_SERVER['argv'] ?? [], 2) as $option) {
            $arg = explode('=', $option);
            $opts[ltrim($arg[0], '--')] = $this->noramlizeArgumentValue($arg[1] ?? true);
        }

        return $opts;
    }

    public function getArgument(string $name, string|int|bool $defaultValue): string|int|bool
    {
        return $this->getArguments()[$name] ?? $defaultValue;
    }

    /**
     * Outputs a message to the CLI with a newline.
     *
     * @param string $message
     * @return void
     */
    public function stdOut(string $message): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * Styles text in red.
     *
     * @param string $text
     * @return string
     */
    public function stdTextRed(string $text): string
    {
        return self::COLOR_RED . $text . self::COLOR_RESET;
    }

    /**
     * Styles text in yellow.
     *
     * @param string $text
     * @return string
     */
    public function stdTextYellow(string $text): string
    {
        return self::COLOR_YELLOW . $text . self::COLOR_RESET;
    }

    /**
     * Styles text in green.
     *
     * @param string $text
     * @return string
     */
    public function stdTextGreen(string $text): string
    {
        return self::COLOR_GREEN . $text . self::COLOR_RESET;
    }

    private function noramlizeArgumentValue(int|string|bool $value): string|int|bool
    {
        if (is_string($value) && strtolower($value) === 'true') {
            return true;
        }

        if (is_string($value) && strtolower($value) === 'false') {
            return false;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }
}
