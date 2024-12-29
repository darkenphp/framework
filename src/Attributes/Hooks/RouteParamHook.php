<?php

declare(strict_types=1);

namespace Darken\Attributes\Hooks;

use Darken\Attributes\RouteParam;
use Darken\Builder\Compiler\Extractor\AttributeExtractorInterface;
use Darken\Builder\Compiler\Extractor\PropertyAttribute;
use Darken\Builder\Hooks\PropertyAttributeHook;
use Darken\Builder\OutputPage;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\Node\Stmt\ClassMethod;

class RouteParamHook extends PropertyAttributeHook
{
    public function isValidAttribute(AttributeExtractorInterface $attribute): bool
    {
        return $attribute->getDecoratorAttributeName() === RouteParam::class;
    }

    public function compileConstructorHook(PropertyAttribute $attribute, ClassMethod $constructor): ClassMethod
    {
        $constructor->stmts[] = $attribute->createGetDataExpressionForCompile('routeParams');
        return $constructor;
    }

    public function polyfillConstructorHook(PropertyAttribute $attribute, Method $constructor): Method
    {
        // should be renamed to "first decorrator ..."
        $paramName = $attribute->getDecoratorAttributeParamValue() ?? $attribute->getName();

        // 1. add the constructor param to the constructor
        $constructor->addParam($this->createParam($paramName, $attribute->getType(), $attribute->getDefaultValue()));

        // 2. add the setter of the value into the runtime from the constructor
        $constructor->addStmt($attribute->createSeteDataExpressionForPolyfill('routeParams'));

        return $constructor;
    }

    public function polyfillClassHook(PropertyAttribute $attribute, Class_ $builder): Class_
    {
        return $builder;
    }

    public function pageDataHook(PropertyAttribute $attribute, OutputPage $page): array
    {
        return [];
    }
}
