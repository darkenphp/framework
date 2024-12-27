<?php

declare(strict_types=1);

namespace Darken\Attributes\Hooks;

use Darken\Attributes\QueryParam;
use Darken\Builder\Compiler\Extractor\AttributeExtractorInterface;
use Darken\Builder\Compiler\Extractor\PropertyAttribute;
use Darken\Builder\Hooks\PropertyAttributeHook;
use PhpParser\Node\Stmt\ClassMethod;

class QueryParamHook extends PropertyAttributeHook
{
    public function compileConstructorHook(PropertyAttribute $attribute, ClassMethod $constructor): ClassMethod
    {
        $constructor->stmts[] = $attribute->createAssignExpression('getQueryParam');
        return $constructor;
    }

    public function isValidAttribute(AttributeExtractorInterface $attribute): bool
    {
        return $attribute->getDecoratorAttributeName() === QueryParam::class;
    }
}
