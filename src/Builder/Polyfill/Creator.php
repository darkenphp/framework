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

        // 2) Get a fully sorted constructor builder
        $sortedConstructorBuilder = $this->fixConstructorPropertySorting($originalConstructorBuilder);

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

    private function fixConstructorPropertySorting(Method $originalConstructor): Method
    {
        // 1) Get the underlying AST node
        $oldNode = $originalConstructor->getNode();

        // 2) Verify it's actually a constructor
        if ($oldNode->name->toString() !== '__construct') {
            throw new InvalidArgumentException('Not a constructor!');
        }

        // 3) Separate required vs. optional
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
        
        // 4) Sort each group alphabetically by parameter name for consistent ordering
        usort($required, function($a, $b) {
            return strcmp($a->var->name, $b->var->name);
        });
        
        usort($optional, function($a, $b) {
            return strcmp($a->var->name, $b->var->name);
        });

        // 5) Merge them in required-first order
        $sortedParams = array_merge($required, $optional);

        // 6) Build a brand-new Method builder with sorted params + original statements
        $factory = new BuilderFactory();

        return $factory
            ->method('__construct')
            ->makePublic()
            // Add sorted parameters
            ->addParams($sortedParams)
            // Reuse the method body/statement block
            ->addStmts($oldNode->stmts);
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
