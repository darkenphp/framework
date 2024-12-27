<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler\Extractor;

use PhpParser\Node\Expr;

interface AttributeExtractorInterface
{
    public function getDecoratorFirstArgument(): Expr|null;

    public function getDecoratorAttributeArguments(): array;

    // #[Inject($this)] <= getDecoratorAttributeParamValue = $this
    public function getDecoratorAttributeParamValue(): string|null|array;

    // #[Inject()] <= getDecoratorAttributeName = Inject
    public function getDecoratorAttributeName(): string|false;
}
