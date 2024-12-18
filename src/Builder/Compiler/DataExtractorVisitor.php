<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler;

use Darken\Attributes\Middleware;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

class DataExtractorVisitor extends NodeVisitorAbstract
{
    private array $data = [
        'middlewares' => [],
    ];

    private PrettyPrinter $printer;

    private UseStatementCollector $useStatementCollector;

    public function __construct(UseStatementCollector $useStatementCollector)
    {
        $this->printer = new PrettyPrinter();
        $this->useStatementCollector = $useStatementCollector;
    }

    public function enterNode(Node $node)
    {
        // Check if the node is a class (including anonymous classes)
        if ($node instanceof ClassLike) {
            // Iterate through attribute groups
            foreach ($node->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attribute) {
                    $attrName = $this->resolveAttributeName($attribute->name);
                    if ($attrName === Middleware::class) {
                        $middlewareData = $this->parseMiddlewareAttribute($attribute);

                        $this->data['middlewares'][] = $middlewareData;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get the extracted data.
     */
    public function getData(string $key, $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Resolve the fully qualified attribute name using use statements.
     */
    private function resolveAttributeName(Name $name): string
    {
        $nameStr = $name->toString();

        // Handle fully qualified names
        if ($name->isFullyQualified()) {
            return ltrim($nameStr, '\\');
        }

        // Check if the first part is an alias in use statements
        //$parts = explode('\\', $nameStr);
        //$firstPart = $parts[0];

        return $this->useStatementCollector->ensureClassName($nameStr);
    }

    /**
     * Parse Middleware attribute and extract its parameters.
     */
    private function parseMiddlewareAttribute(Node\Attribute $attribute): array
    {
        $args = $attribute->args;
        $middlewareClass = '';
        $params = [];
        $position = 'before'; // Default position

        foreach ($args as $index => $arg) {
            $value = $arg->value;

            if ($index === 0) {
                // First argument: Middleware Class
                if ($value instanceof ClassConstFetch) {
                    $middlewareClass = $this->resolveClassConstFetch($value);
                } elseif ($value instanceof Name) {
                    $middlewareClass = $this->resolveAttributeName($value);
                }
            } elseif ($index === 1) {
                // Second argument: Params Array
                if ($value instanceof Array_) {
                    $params = $this->parseArray($value);
                }
            } elseif ($index === 2) {
                // Third argument: Position
                if ($value instanceof ClassConstFetch) {
                    $position = $this->resolveClassConstFetch($value) . '::' . $value->name->toString();
                } elseif ($value instanceof ConstFetch) {
                    $position = $this->resolveConstFetch($value);
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
     * Resolve ClassConstFetch expressions like MiddlewarePosition::BEFORE
     */
    private function resolveClassConstFetch(ClassConstFetch $node): string
    {
        return $this->resolveAttributeName($node->class);
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
    private function parseArray(Array_ $array): array
    {
        $result = [];

        foreach ($array->items as $item) {
            if ($item->key instanceof String_) {
                $key = $item->key->value;
            } elseif ($item->key instanceof Int_) {
                $key = $item->key->value;
            } else {
                // Handle other key types if necessary
                $key = $this->printer->prettyPrintExpr($item->key);
            }

            $value = $this->getValueFromExpr($item->value);
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Extract value from an expression node.
     */
    private function getValueFromExpr(Node\Expr $expr)
    {
        if ($expr instanceof String_) {
            return $expr->value;
        } elseif ($expr instanceof Int_) {
            return $expr->value;
        } elseif ($expr instanceof ConstFetch) {
            return $this->resolveConstFetch($expr);
        } elseif ($expr instanceof ClassConstFetch) {
            return $this->resolveClassConstFetch($expr);
        } elseif ($expr instanceof Array_) {
            return $this->parseArray($expr);
        }

        // Add more cases as needed
        return null;
    }
}