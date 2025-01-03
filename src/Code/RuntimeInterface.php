<?php

declare(strict_types=1);

namespace Darken\Code;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface RuntimeInterface
 *
 * This interface is used to define the render method for a class that returns a string or a Response object.
 */
interface RuntimeInterface
{
    public function render(): string|ResponseInterface;
}
