<?php

declare(strict_types=1);

namespace Darken\Builder\Polyfill;

use Darken\Builder\Compiler\DataExtractorVisitor;
use Darken\Builder\Compiler\PropertyExtractor;
use Darken\Builder\OutputPolyfill;
use Darken\Code\Runtime;
use InvalidArgumentException;
use PhpParser\Builder;
use PhpParser\Builder\Method;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Param;

class Creator
{
    public $baseRuntimeClass = Runtime::class;

    public function createNode(OutputPolyfill $outputPolyfill): Node
    {
        $factory = new BuilderFactory();

        // Build the namespace
        $namespaceBuilder = $factory->namespace($outputPolyfill->getNamespace());

        // 1) Fetch the original constructor builder
        $originalConstructorBuilder = $this->getConstructorMethod($outputPolyfill->compilerOutput->data);

        // 2) Get a fully sorted constructor builder
        $sortedConstructorBuilder = $this->fixConstructorPropertySorting($originalConstructorBuilder);

        // 3) Build the class with the *sorted* constructor
        $classBuilder = $factory
            ->class($outputPolyfill->getClassName())
            ->extend($this->transformClassToFullqualified($this->baseRuntimeClass))
            ->addStmt($sortedConstructorBuilder) // Use the new (correctly sorted) method builder
            ->addStmts($this->getSlotMethods($outputPolyfill->compilerOutput->data))
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
        // Merge them in required-first order
        $sortedParams = array_merge($required, $optional);

        // 4) Build a brand-new Method builder with sorted params + original statements
        $factory = new BuilderFactory();

        return $factory
            ->method('__construct')
            ->makePublic()
            // Add sorted parameters
            ->addParams($sortedParams)
            // Reuse the method body/statement block
            ->addStmts($oldNode->stmts);

        // 5) Return the new builder that is guaranteed sorted
    }

    private function getConstructorMethod(DataExtractorVisitor $extractor): Method
    {
        $factory = new BuilderFactory();
        $constructor = $extractor->getData('constructor', []);

        // If there is no constructor data, return an empty __construct()
        //if (count($constructor) === 0) {
        //return $factory->method('__construct');
        //}

        // 1) Split into required vs optional
        $requiredProps = [];
        $optionalProps = [];

        foreach ($constructor as $prop) {
            // If there's no default value, it's required
            if ($prop->getDefaultValue() === null) {
                $requiredProps[] = $prop;
            } else {
                $optionalProps[] = $prop;
            }
        }

        // 2) Merge them so required come first
        $sortedProps = array_merge($requiredProps, $optionalProps);

        // 3) Build the __construct
        $methodBuilder = $factory->method('__construct')
            ->makePublic();

        foreach ($sortedProps as $prop) {
            /** @var PropertyExtractor $prop */
            $paramName = $prop->getDecoratorAttributeParamValue() ?? $prop->getName();

            // 1) Build the param
            $param = $factory->param($paramName);

            // 2) If there's a type, set it
            if ($prop->getType() !== null) {
                $param->setType($prop->getType());
            }

            // 3) If there's a default value, set it
            if ($prop->getDefaultValue() !== null) {
                // Here you likely want to handle strings, arrays, etc. properly,
                // but for simplicity we do:
                $param->setDefault($prop->getDefaultValue());
            }

            // 4) Add param to the method
            $methodBuilder->addParam($param);

            // 5) Example assignment statement
            $methodBuilder->addStmt(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable('this'),
                    'setArgumentParam',
                    [
                        new Node\Arg(new Node\Scalar\String_($paramName)),
                        new Node\Arg(new Node\Expr\Variable($paramName)),
                    ]
                )
            );
        }

        // $method
        $extractor->onPolyfillConstructorHook($methodBuilder);

        return $methodBuilder;
    }

    private function getSlotMethods(DataExtractorVisitor $extractor): array
    {
        $factory = new BuilderFactory();
        $slots = $extractor->getData('slots', []);

        // If there are no slots, return an empty array of methods
        if (count($slots) === 0) {
            return [];
        }

        $methods = [];

        foreach ($slots as $slot) {
            // @var PropertyExtractor $slot
            $methodName = $slot->getDecoratorAttributeParamValue() ?: $slot->getName();
            $startTag = 'open' . ucfirst($methodName);
            $closeTag = 'close' . ucfirst($methodName);

            // openXyz()
            $openMethod = $factory->method($startTag)
                ->makePublic()
                ->setReturnType('self')
                ->addStmt(
                    // ob_start();
                    new Node\Expr\FuncCall(new Node\Name('ob_start'))
                )
                ->addStmt(
                    // return $this;
                    new Node\Stmt\Return_(new Node\Expr\Variable('this'))
                );

            // closeXyz()
            $closeMethod = $factory->method($closeTag)
                ->makePublic()
                ->setReturnType('self')
                ->addStmt(
                    // $this->setSlot('xyz', ob_get_clean());
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable('this'),
                        'setSlot',
                        [
                            new Node\Arg(new Node\Scalar\String_($methodName)),
                            new Node\Arg(
                                new Node\Expr\FuncCall(new Node\Name('ob_get_clean'))
                            ),
                        ]
                    )
                )
                ->addStmt(
                    // return $this;
                    new Node\Stmt\Return_(new Node\Expr\Variable('this'))
                );

            // Collect both new methods
            $methods[] = $openMethod;
            $methods[] = $closeMethod;
        }

        return $methods;
    }
}
