<?php

declare(strict_types=1);

namespace Darken\Attributes\Hooks;

use Darken\Attributes\ConstructorParam;
use Darken\Builder\Compiler\Extractor\AttributeExtractorInterface;
use Darken\Builder\Compiler\Extractor\PropertyAttribute;
use Darken\Builder\Hooks\PropertyAttributeHook;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;

class ConstructorParamHook extends PropertyAttributeHook
{
    public function isValidAttribute(AttributeExtractorInterface $attribute): bool
    {
        return $attribute->getDecoratorAttributeName() === ConstructorParam::class;
    }

    public function compileConstructorHook(PropertyAttribute $attribute, ClassMethod $constructor): ClassMethod
    {
        $constructor->stmts[] = $attribute->createAssignExpression('getArgumentParam');
        return $constructor;
    }

    public function polyfillConstructorHook(PropertyAttribute $attribute, Method $constructor): Method
    {
        $paramName = $attribute->getDecoratorAttributeParamValue() ?? $attribute->getName();

        $constructor->addParam($this->createParam($paramName, $attribute->getType(), $attribute->getDefaultValue()));

        $constructor->addStmt(new MethodCall(
            new Variable('this'),
            'setArgumentParam',
            [
                new Arg(new String_($paramName)),
                new Arg(new Variable($paramName)),
            ]
        ));

        return $constructor;
    }

    public function polyfillClassHook(PropertyAttribute $attribute, Class_ $builder): Class_
    {
        return $builder;
    }
}
