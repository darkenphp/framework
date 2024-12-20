<?php

declare(strict_types=1);

namespace Darken\Builder;

use Darken\Builder\Compiler\DataExtractorVisitor;

class CodeCompilerOutput
{
    public function __construct(private string $code, public DataExtractorVisitor $data)
    {

    }

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return array<mixed>
     */
    public function getMeta(string $key, mixed $default = []): array
    {
        return $this->data->getData($key, $default);
    }
}
