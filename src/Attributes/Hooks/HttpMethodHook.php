<?php

declare(strict_types=1);

namespace Darken\Attributes\Hooks;

use Darken\Attributes\HttpMethod;
use Darken\Builder\Compiler\Extractor\AttributeExtractorInterface;
use Darken\Builder\Compiler\Extractor\ClassAttribute;
use Darken\Builder\Hooks\ClassAttributeHook;
use Darken\Builder\OutputPage;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;

class HttpMethodHook extends ClassAttributeHook
{
    public function isValidAttribute(AttributeExtractorInterface $attribute): bool
    {
        return $attribute->getDecoratorAttributeName() === HttpMethod::class;
    }

    public function compileConstructorHook(ClassAttribute $attribute, ClassMethod $constructor): ClassMethod
    {
        return $constructor;
    }

    public function polyfillConstructorHook(ClassAttribute $attribute, Method $constructor): Method
    {
        return $constructor;
    }

    public function polyfillClassHook(ClassAttribute $attribute, Class_ $builder): Class_
    {
        return $builder;
    }

    public function pageDataHook(ClassAttribute $attribute, OutputPage $page): array
    {
        $argument = $attribute->getDecoratorFirstArgument();

        if ($argument instanceof String_) {
            $methods = explode(',', $argument->value);
        } elseif ($argument instanceof Array_) {
            $methods = $this->parseArray($argument);
        }

        if (!isset($methods)) {
            return [];
        }

        return [
            'methods' => array_map('strtoupper', $methods),
        ];
    }
}
