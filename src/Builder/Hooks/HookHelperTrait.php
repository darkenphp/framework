<?php

declare(strict_types=1);

namespace Darken\Builder\Hooks;

use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;

trait HookHelperTrait
{
    public function createParam(string $paramName, string $type, mixed $default = null): Param
    {
        $factory = new BuilderFactory();

        // 1) Build the param
        $param = $factory->param($paramName);

        // 2) If there's a type, set it
        $param->setType($type);

        // 3) If there's a default value, set it
        if ($default !== null) {
            // Here you likely want to handle strings, arrays, etc. properly,
            // but for simplicity we do:
            $param->setDefault($default);
        }

        return $param;

    }

    /**
     * Parse an Array_ node into a PHP associative array.
     */
    public function parseArray(Array_ $array): array
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
    public function getValueFromExpr(Expr $expr): string|int|array|null
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
