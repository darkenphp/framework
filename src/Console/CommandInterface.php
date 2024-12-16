<?php

declare(strict_types=1);

namespace Darken\Console;

interface CommandInterface
{
    public function run(Application $app): void;
}
