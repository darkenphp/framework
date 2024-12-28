<?php

declare(strict_types=1);

namespace Darken\Attributes\Hooks;

use Darken\Attributes\Slot;
use Darken\Builder\Compiler\Extractor\AttributeExtractorInterface;
use Darken\Builder\Compiler\Extractor\PropertyAttribute;
use Darken\Builder\Hooks\PropertyAttributeHook;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\BuilderFactory;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;

class SlotHook extends PropertyAttributeHook
{
    public function isValidAttribute(AttributeExtractorInterface $attribute): bool
    {
        return $attribute->getDecoratorAttributeName() === Slot::class;
    }

    public function compileConstructorHook(PropertyAttribute $attribute, ClassMethod $constructor): ClassMethod
    {
        $constructor->stmts[] = $attribute->createAssignExpression('getSlot');
        return $constructor;
    }

    public function polyfillConstructorHook(PropertyAttribute $attribute, Method $constructor): Method
    {
        return $constructor;
    }

    public function polyfillClassHook(PropertyAttribute $attribute, Class_ $builder): Class_
    {
        $methodName = $attribute->getDecoratorAttributeParamValue() ?: $attribute->getName();
        $startTag = 'open' . ucfirst($methodName);
        $closeTag = 'close' . ucfirst($methodName);

        $b = new BuilderFactory();

        $openMethod = $b->method($startTag)
            ->makePublic()
            ->setReturnType('self')
            ->addStmt(
                new FuncCall(new Name('ob_start'))
            )
            ->addStmt(
                new Return_(new Variable('this'))
            );

        $builder->addStmt($openMethod);

        $closeMethod = $b->method($closeTag)
            ->makePublic()
            ->setReturnType('self')
            ->addStmt(
                new MethodCall(
                    new Variable('this'),
                    'setSlot',
                    [
                        new Arg(new String_($methodName)),
                        new Arg(
                            new FuncCall(new Name('ob_get_clean'))
                        ),
                    ]
                )
            )
            ->addStmt(
                new Return_(new Variable('this'))
            );

        $builder->addStmt($closeMethod);
        return $builder;

    }
}
