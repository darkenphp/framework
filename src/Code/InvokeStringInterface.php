<?php

declare(strict_types=1);

namespace Darken\Code;

interface InvokeStringInterface
{
    public function __invoke(): string;
}
