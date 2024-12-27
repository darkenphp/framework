<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler\Extractor;

use Darken\Builder\Compiler\UseStatementCollector;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;

trait AttributeExtractorTrait
{
    public function createDecoratorAttributeArguments(Attribute $decoratorAttribute): array
    {
        return $decoratorAttribute->args ?? [];
    }

    public function createDecoratorAttributeFirstArgument(Attribute $decoratorAttribute): Expr|null
    {
        $args = $this->createDecoratorAttributeArguments($decoratorAttribute);
        return isset($args[0]) ? $args[0]->value : null;
    }

    public function createDecoratorAttributeParamValue(UseStatementCollector $statemnentCollector, Attribute $decoratorAttribute): string|null|array
    {
        $dectoratorAttributeFirstArgument = $this->createDecoratorAttributeFirstArgument($decoratorAttribute);

        if ($dectoratorAttributeFirstArgument instanceof String_) {
            return $dectoratorAttributeFirstArgument->value;
        } elseif ($dectoratorAttributeFirstArgument instanceof Array_) {
            $vals = [];
            foreach ($dectoratorAttributeFirstArgument->items as $item) {
                $vals[] = $item->value->value ?? null;
            }
            return $vals;
        } elseif ($dectoratorAttributeFirstArgument instanceof ClassConstFetch) {
            return $statemnentCollector->ensureClassName($dectoratorAttributeFirstArgument->class->name);
        }

        return null;
    }

    public function createDecoratorAttributeParamName(UseStatementCollector $useStatementCollector, Attribute $decoratorAttribute): string|false
    {
        if ($decoratorAttribute->name instanceof FullyQualified) {
            return $decoratorAttribute->name->toString();
        }

        return ltrim($useStatementCollector->ensureClassName($decoratorAttribute->name->toString()), '\\');
    }
}
