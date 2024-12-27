<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler;

use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;

class AttributeExtractor implements AttributeInterface
{
    public function __construct(private UseStatementCollector $useStatementCollector, private Attribute $decoratorAttribute)
    {

    }

    public function getFirstArgument(): Expr|null
    {
        return isset($this->decoratorAttribute->args[0]) ? $this->decoratorAttribute->args[0]->value : null;
    }

    public function getDecoratorAttributeArguments(): array
    {
        return $this->decoratorAttribute->args ?? [];
    }

    // #[Inject($this)] <= getDecoratorAttributeParamValue = $this
    public function getDecoratorAttributeParamValue(): string|null|array
    {
        $dectoratorAttributeFirstArgument = $this->getFirstArgument();

        if ($dectoratorAttributeFirstArgument instanceof String_) {
            return $dectoratorAttributeFirstArgument->value;
        } elseif ($dectoratorAttributeFirstArgument instanceof Array_) {
            $vals = [];
            foreach ($dectoratorAttributeFirstArgument->items as $item) {
                $vals[] = $item->value->value;
            }
            return $vals;
        } elseif ($dectoratorAttributeFirstArgument instanceof ClassConstFetch) {
            return $this->useStatementCollector->ensureClassName($dectoratorAttributeFirstArgument->class->name);
        }

        return null;
    }

    // #[Inject()] <= getDecoratorAttributeName = Inject
    public function getDecoratorAttributeName(): string|false
    {
        if ($this->decoratorAttribute->name instanceof FullyQualified) {
            return $this->decoratorAttribute->name->toString();
        }

        return ltrim($this->useStatementCollector->ensureClassName($this->decoratorAttribute->name->toString()), '\\');
    }
}
