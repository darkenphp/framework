<?php

declare(strict_types=1);

namespace Darken\Attributes\Hooks;

use Darken\Attributes\Inject;
use Darken\Builder\Compiler\Extractor\AttributeExtractorInterface;
use Darken\Builder\Compiler\Extractor\PropertyAttribute;
use Darken\Builder\Hooks\PropertyAttributeHook;
use Darken\Builder\OutputPage;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\Node\Stmt\ClassMethod;

class InjectHook extends PropertyAttributeHook
{
    public function isValidAttribute(AttributeExtractorInterface $attribute): bool
    {
        return $attribute->getDecoratorAttributeName() === Inject::class;
    }

    public function compileConstructorHook(PropertyAttribute $attribute, ClassMethod $constructor): ClassMethod
    {
        // renmove to "resolve" container
        $constructor->stmts[] = $attribute->createGetContainerExpressionForCompile();
        return $constructor;
    }

    public function polyfillConstructorHook(PropertyAttribute $attribute, Method $constructor): Method
    {
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
