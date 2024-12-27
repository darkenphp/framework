<?php

declare(strict_types=1);

namespace Darken\Builder\Polyfill;

use Darken\Builder\Compiler\DataExtractorVisitor;
use Darken\Builder\Compiler\PropertyExtractor;
use Darken\Builder\OutputPolyfill;
use PhpParser\Builder;
use PhpParser\BuilderFactory;
use PhpParser\Node;

class Creator
{
    public function createNode(OutputPolyfill $outputPolyfill): Node
    {
        $factory = new BuilderFactory();

        // Define namespace
        $namespace = $factory->namespace($outputPolyfill->getNamespace());

        // Define class
        $class = $factory->class($outputPolyfill->getClassName())
            ->extend('\\Darken\\Code\\Runtime')
            ->addStmt($this->getConstructorMethod($outputPolyfill->compilerOutput->data))
            ->addStmts($this->getSlotMethods($outputPolyfill->compilerOutput->data))
            ->addStmt(
                $factory->method('renderFilePath')
                    ->makePublic()
                    ->setReturnType('string')
                    ->addStmt(
                        new Node\Stmt\Return_(
                            new Node\Expr\BinaryOp\Concat(
                                // First concat: dirname(__FILE__) . DIRECTORY_SEPARATOR
                                new Node\Expr\BinaryOp\Concat(
                                    new Node\Expr\FuncCall(
                                        new Node\Name('dirname'),
                                        [
                                            new Node\Arg(
                                                new Node\Expr\ConstFetch(
                                                    new Node\Name('__FILE__')
                                                )
                                            ),
                                        ]
                                    ),
                                    new Node\Expr\ConstFetch(
                                        new Node\Name('DIRECTORY_SEPARATOR')
                                    )
                                ),
                                // Second concat: ... . 'layout1.compiled.php'
                                new Node\Scalar\String_(
                                    $outputPolyfill->getRelativeBuildOutputFilePath()
                                )
                            )
                        )
                    )
            )
            ->getNode();

        foreach ($outputPolyfill->compilerOutput->data->getProperties() as $dataProp) {
            foreach ($outputPolyfill->compilerOutput->attributeHandlers as $handler) {
                if ($handler->isAttributeAccepted($dataProp)) {
                    $handler->polyfillCreatorHook($namespace, $dataProp);
                }
            }
        }

        // Add class to namespace
        $namespace->addStmt($class);

        return $namespace->getNode();
    }

    private function getConstructorMethod(DataExtractorVisitor $extractor): Builder
    {
        $factory = new BuilderFactory();
        $constructor = $extractor->getData('constructor', []);

        // If there is no constructor data, return an empty __construct()
        if (count($constructor) === 0) {
            return $factory->method('__construct');
        }

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
