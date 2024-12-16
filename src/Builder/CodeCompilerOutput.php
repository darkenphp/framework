<?php

declare(strict_types=1);

namespace Darken\Builder;

class CodeCompilerOutput
{
    public function __construct(public string $code, public array $meta)
    {

    }
}
