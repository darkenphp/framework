<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler;

interface AttributeInterface
{
    public function getDecoratorAttributeParamValue(): string|null|array;

    public function getDecoratorAttributeName(): string|false;

    public function getDecoratorAttributeArguments(): array;
}
