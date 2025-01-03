<?php

declare(strict_types=1);

namespace Darken\Code;

/**
 * Interface InvokeStringInterface
 *
 * This interface is used to define the __invoke method for a class that returns a string.
 */
interface InvokeStringInterface
{
    public function __invoke(): string;
}
