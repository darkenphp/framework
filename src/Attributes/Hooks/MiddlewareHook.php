<?php

declare(strict_types=1);

namespace Darken\Attributes\Hooks;

use Darken\Attributes\Middleware;
use Darken\Builder\Compiler\Extractor\AttributeExtractorInterface;
use Darken\Builder\Compiler\Extractor\ClassAttribute;
use Darken\Builder\Hooks\ClassAttributeHook;
use Darken\Builder\OutputPage;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;

class MiddlewareHook extends ClassAttributeHook
{
    public function isValidAttribute(AttributeExtractorInterface $attribute): bool
    {
        return $attribute->getDecoratorAttributeName() === Middleware::class;
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
        return [
            'middlewares' => [$this->parseMiddlewareAttribute($attribute)],
        ];
    }

    private function parseMiddlewareAttribute(ClassAttribute $attribute): array
    {
        $args = $attribute->getDecoratorAttributeArguments();
        $middlewareClass = '';
        $params = [];
        $position = 'before'; // Default position

        foreach ($args as $index => $arg) {
            $value = $arg->value;

            if ($index === 0) {
                // First argument: Middleware Class
                if ($value instanceof ClassConstFetch) {
                    $middlewareClass = $attribute->resolveAttributeName($attribute->useStatementCollector, $value->class);
                }
            } elseif ($index === 1) {
                // Second argument: Params Array
                if ($value instanceof Array_) {
                    $params = $this->parseArray($value);
                }
            } elseif ($index === 2) {
                // Third argument: Position
                if ($value instanceof ClassConstFetch) {
                    $position = $attribute->resolveAttributeName($attribute->useStatementCollector, $value->class) . '::' . $value->name->toString();
                } elseif ($value instanceof String_) {
                    $position = $value->value;
                }
            }
        }

        return [
            'class' => $middlewareClass,
            'params' => $params,
            'position' => $position,
        ];
    }

    /**
     * Parse an Array_ node into a PHP associative array.
     */
    private function parseArray(Array_ $array): array
    {
        $result = [];

        foreach ($array->items as $item) {
            if ($item->key instanceof String_) {
                $result[$item->key->value] = $this->getValueFromExpr($item->value);
            } elseif ($item->key instanceof Int_) {
                $result[$item->key->value] = $this->getValueFromExpr($item->value);
            }
        }

        return $result;
    }

    /**
     * Extract value from an expression node.
     */
    private function getValueFromExpr(Expr $expr): string|int|array|null
    {
        if ($expr instanceof String_) {
            return $expr->value;
        } elseif ($expr instanceof Int_) {
            return $expr->value;
        } elseif ($expr instanceof Array_) {
            return $this->parseArray($expr);
        }

        return null;
    }
}
