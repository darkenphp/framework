<?php

declare(strict_types=1);

namespace Darken\Builder;

use Darken\Builder\Compiler\DataExtractorVisitor;

class CodeCompilerOutput
{
    public function __construct(public string $code, public array $meta, public DataExtractorVisitor $data)
    {

    }
}
