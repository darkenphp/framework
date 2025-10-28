<?php

declare(strict_types=1);

namespace Darken\Console;

use Darken\Console\Commands\Build;
use Darken\Console\Commands\Dev;
use Darken\Console\Commands\Watch;
use Darken\Enum\ConsoleExit;
use Darken\Kernel;
use Exception;
use Whoops\Handler\PlainTextHandler;

/**
 * Console Application
 *
 * This class is used to define the application and its commands.
 */
class Application extends Kernel
{
    // ANSI color codes
    private const COLOR_RED = "\033[31m";

    private const COLOR_YELLOW = "\033[33m";

    private const COLOR_GREEN = "\033[32m";

    private const COLOR_RESET = "\033[0m";

    private $commands = [];

    public function initalize(): void
    {
        $this->whoops->pushHandler(new PlainTextHandler());
        $this->whoops->register();

        $this->registerCommand('build', Build::class);
        $this->registerCommand('dev', Dev::class);
        $this->registerCommand('watch', Watch::class);
    }

    public function registerCommand(string $name, string|CommandInterface $object, array $params = []): void
    {
        $this->commands[$name] = is_object($object) ? $object : [$object, $params];
    }

    public function run(): int
    {
        $command = array_key_exists($this->getCommandName(), $this->commands) ? $this->commands[$this->getCommandName()] : null;

        if (!$command) {
            $this->stdOut($this->stdTextRed(sprintf('Command "%s" not found.', $this->getCommandName())));
            return ConsoleExit::INVALID_INPUT->value;
        }

        try {
            /** @var CommandInterface $object */
            $object = $this->getContainerService()->ensure($command, CommandInterface::class);
            return $object->run($this)->value;
        } catch (Exception $e) {
            //$this->stdOut($this->stdTextRed($e->getMessage()));
            $this->whoops->handleException($e);
            return ConsoleExit::ERROR->value;
        }
    }

    public function getBin(): string
    {
        return $_SERVER['argv'][0];
    }

    public function getCommandName(): ?string
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
