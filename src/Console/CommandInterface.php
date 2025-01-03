<?php

declare(strict_types=1);

namespace Darken\Console;

/**
 * Interface CommandInterface
 *
 * This interface is used to define the run method for a class that runs a command.
 */
interface CommandInterface
{
    public function run(Application $app): void;
}
