<?php

declare(strict_types=1);

namespace Darken\Console;

use Darken\Console\Commands\Build;
use Darken\Console\Commands\Dev;
use Darken\Console\Commands\Watch;
use Darken\Kernel;

class Application extends Kernel
{
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

    public function stdOut(string $message): void
    {
        echo $message . PHP_EOL;
    }
}
