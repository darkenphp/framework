<?php

declare(strict_types=1);

namespace Darken\Code;

use Psr\Http\Message\ResponseInterface;

interface RuntimeInterface
{
    public function render(): string|ResponseInterface;
}
