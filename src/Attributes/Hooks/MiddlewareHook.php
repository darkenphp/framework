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
use PhpParser\Node\Expr\ConstFetch;
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
                    $params = $this->parseArray($attribute, $value);
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
     * Resolve ConstFetch expressions like BEFORE
     */
    private function resolveConstFetch(ConstFetch $node): string
    {
        return $node->name->toString();
    }

    /**
     * Parse an Array_ node into a PHP associative array.
     */
    private function parseArray(ClassAttribute $attribute, Array_ $array): array
    {
        $result = [];

        foreach ($array->items as $item) {
            if ($item->key instanceof String_) {
                $key = $item->key->value;
            } elseif ($item->key instanceof Int_) {
                $key = $item->key->value;
            } else {
                continue;
            }

            $value = $this->getValueFromExpr($attribute, $item->value);
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Extract value from an expression node.
     */
    private function getValueFromExpr(ClassAttribute $attribute, Expr $expr)
    {
        if ($expr instanceof String_) {
            return $expr->value;
        } elseif ($expr instanceof Int_) {
            return $expr->value;
        } elseif ($expr instanceof ConstFetch) {
            return $this->resolveConstFetch($expr);
        } elseif ($expr instanceof ClassConstFetch) {
            return $attribute->resolveAttributeName($attribute->useStatementCollector, $expr->class);
        } elseif ($expr instanceof Array_) {
            return $this->parseArray($attribute, $expr);
        }

        // Add more cases as needed
        return null;
    }
}
