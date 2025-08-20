<?php

declare(strict_types=1);

namespace Darken\Builder\Polyfill;

use Darken\Builder\Compiler\DataExtractorVisitor;
use Darken\Builder\OutputPolyfill;
use Darken\Code\Runtime;
use InvalidArgumentException;
use PhpParser\Builder;
use PhpParser\Builder\Method;
use PhpParser\BuilderFactory;
use PhpParser\Node;

class Creator
{
    public $baseRuntimeClass = Runtime::class;

    public function createNode(OutputPolyfill $outputPolyfill): Node
    {
        $factory = new BuilderFactory();
        $namespaceBuilder = $factory->namespace($outputPolyfill->getNamespace());

        // 1) Fetch the original constructor builder
        $originalConstructorBuilder = $this->getConstructorMethod($outputPolyfill->compilerOutput->data);

        // 2) Get a fully sorted constructor builder with order awareness
        $sortedConstructorBuilder = $this->fixConstructorPropertySorting($originalConstructorBuilder, $outputPolyfill->compilerOutput->data);

        // 3) Build the class with the *sorted* constructor
        $classBuilder = $factory
            ->class($outputPolyfill->getClassName())
            ->extend($this->transformClassToFullqualified($this->baseRuntimeClass))
            ->addStmt($sortedConstructorBuilder)
            ->addStmt(
                $factory->method('renderFilePath')
                    ->makePublic()
                    ->setReturnType('string')
                    ->addStmt(
                        new Node\Stmt\Return_(
                            new Node\Expr\BinaryOp\Concat(
                                new Node\Expr\BinaryOp\Concat(
                                    new Node\Expr\FuncCall(
                                        new Node\Name('dirname'),
                                        [
                                            new Node\Arg(
                                                new Node\Expr\ConstFetch(new Node\Name('__FILE__'))
                                            ),
                                        ]
                                    ),
                                    new Node\Expr\ConstFetch(new Node\Name('DIRECTORY_SEPARATOR'))
                                ),
                                new Node\Scalar\String_($outputPolyfill->getRelativeBuildOutputFilePath())
                            )
                        )
                    )
            );

        $classNode = $outputPolyfill->compilerOutput->data->onPolyfillClassHook($classBuilder);
        // 4) Get the Class_ node
        $classNode = $classBuilder->getNode();
        // 5) Add it to the namespace
        $namespaceBuilder->addStmt($classNode);
        // 6) Return the full namespace AST
        return $namespaceBuilder->getNode();
    }

    private function transformClassToFullqualified(string $class): string
    {
        return '\\' . ltrim($class, '\\');
    }

    private function fixConstructorPropertySorting(Method $originalConstructor, DataExtractorVisitor $extractor): Method
    {
        // 1) Get the underlying AST node
        $oldNode = $originalConstructor->getNode();

        // 2) Verify it's actually a constructor
        if ($oldNode->name->toString() !== '__construct') {
            throw new InvalidArgumentException('Not a constructor!');
        }

        // 3) Get order information from ConstructorParam attributes
        $paramOrders = $this->getParameterOrders($extractor);

        // 4) Separate required vs. optional
        $required = [];
        $optional = [];

        foreach ($oldNode->params as $param) {
            // If $param->default is null => required
            if ($param->default === null) {
                $required[] = $param;
            } else {
                $optional[] = $param;
            }
        }
        
        // 5) Sort each group considering explicit order first, then alphabetically
        // Only apply sorting if explicit ordering is used; otherwise preserve declaration order
        if ($this->hasExplicitOrdering($paramOrders)) {
            usort($required, function($a, $b) use ($paramOrders) {
                return $this->compareParameters($a, $b, $paramOrders);
            });
            
            usort($optional, function($a, $b) use ($paramOrders) {
                return $this->compareParameters($a, $b, $paramOrders);
            });
        }
        // If no explicit ordering, keep original declaration order (backward compatibility)

        // 6) Merge them in required-first order
        $sortedParams = array_merge($required, $optional);

        // 7) Reorder statements to match the new parameter order
        $sortedStmts = $this->reorderStatements($oldNode->stmts, $oldNode->params, $sortedParams, $paramOrders);

        // 8) Build a brand-new Method builder with sorted params + reordered statements
        $factory = new BuilderFactory();

        return $factory
            ->method('__construct')
            ->makePublic()
            // Add sorted parameters
            ->addParams($sortedParams)
            // Add reordered statements
            ->addStmts($sortedStmts);
    }

    /**
     * Get parameter order information from ConstructorParam attributes
     */
    private function getParameterOrders(DataExtractorVisitor $extractor): array
    {
        $orders = [];
        foreach ($extractor->getPropertyAttributes() as $propertyAttribute) {
            if ($propertyAttribute->getDecoratorAttributeName() === 'Darken\Attributes\ConstructorParam') {
                $paramName = $propertyAttribute->getDecoratorAttributeParamValue() ?? $propertyAttribute->getName();
                $order = $propertyAttribute->getDecoratorAttributeOrderValue();
                if ($order !== null) {
                    $orders[$paramName] = $order;
                }
            }
        }
        return $orders;
    }

    /**
     * Compare two parameters considering explicit order first, then alphabetical
     */
    private function compareParameters($paramA, $paramB, array $paramOrders): int
    {
        $nameA = $paramA->var->name;
        $nameB = $paramB->var->name;
        
        $orderA = $paramOrders[$nameA] ?? null;
        $orderB = $paramOrders[$nameB] ?? null;
        
        // If both have explicit orders, sort by order
        if ($orderA !== null && $orderB !== null) {
            return $orderA <=> $orderB;
        }
        
        // If only A has an order, A comes first
        if ($orderA !== null && $orderB === null) {
            return -1;
        }
        
        // If only B has an order, B comes first
        if ($orderA === null && $orderB !== null) {
            return 1;
        }
        
        // If neither has an order, sort alphabetically
        return strcmp($nameA, $nameB);
    }

    /**
     * Check if any parameters have explicit order values
     */
    private function hasExplicitOrdering(array $paramOrders): bool
    {
        return !empty($paramOrders);
    }

    /**
     * Reorder statements to match the new parameter order
     */
    private function reorderStatements(array $originalStmts, array $originalParams, array $sortedParams, array $paramOrders): array
    {
        // If no explicit ordering was used, preserve original statement order
        if (!$this->hasExplicitOrdering($paramOrders)) {
            return $originalStmts;
        }

        // Create a mapping from parameter name to statement index
        $paramToStmtMap = [];
        for ($i = 0; $i < count($originalParams); $i++) {
            $paramName = $originalParams[$i]->var->name;
            $paramToStmtMap[$paramName] = $i;
        }

        // Reorder statements based on the new parameter order
        $reorderedStmts = [];
        foreach ($sortedParams as $param) {
            $paramName = $param->var->name;
            if (isset($paramToStmtMap[$paramName])) {
                $stmtIndex = $paramToStmtMap[$paramName];
                if (isset($originalStmts[$stmtIndex])) {
                    $reorderedStmts[] = $originalStmts[$stmtIndex];
                }
            }
        }

        return $reorderedStmts;
    }

    private function getConstructorMethod(DataExtractorVisitor $extractor): Method
    {
        $factory = new BuilderFactory();

        // 3) Build the __construct
        $methodBuilder = $factory->method('__construct')
            ->makePublic();

        // $method
        $extractor->onPolyfillConstructorHook($methodBuilder);

        return $methodBuilder;
    }
}
