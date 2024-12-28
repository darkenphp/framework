<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler\Extractor;

use Darken\Builder\Compiler\UseStatementCollector;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;

class ClassAttribute implements AttributeExtractorInterface
{
    use AttributeExtractorTrait;

    public function __construct(private UseStatementCollector $useStatementCollector, private Attribute $decoratorAttribute)
    {

    }

    public function getDecoratorFirstArgument(): Expr|null
    {
        return $this->createDecoratorAttributeFirstArgument($this->decoratorAttribute);
    }

    public function getDecoratorAttributeArguments(): array
    {
        return $this->createDecoratorAttributeArguments($this->decoratorAttribute);
    }

    public function getDecoratorAttributeParamValue(): string|null|array
    {
        return $this->createDecoratorAttributeParamValue($this->useStatementCollector, $this->decoratorAttribute);
    }

    public function getDecoratorAttributeName(): string|false
    {
        return $this->createDecoratorAttributeParamName($this->useStatementCollector, $this->decoratorAttribute);
    }
}
