<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler\AttributeHandler;

use Darken\Attributes\HttpVerb;
use Darken\Attributes\QueryParam;
use Darken\Builder\Compiler\AttributeExtractor;
use Darken\Builder\Compiler\AttributeInterface;
use PhpParser\Builder\Namespace_;
use PhpParser\Node\Stmt\ClassMethod;

class QueryParamHandler implements AttributeHandlerInterface
{
    public function isAttributeAccepted(AttributeInterface $attribute): bool
    {
        return $attribute->getDecoratorAttributeName() === QueryParam::class;
    }

    public function compileConstructorHook(ClassMethod $method, AttributeExtractor $attribute): ClassMethod
    {

        return $method;
    }

    public function polyfillConstructorHook(Namespace_ $method, AttributeExtractor $attribute): Namespace_
    {
        return $method;
    }

    public function polyfillCreatorHook(Namespace_ $class, AttributeExtractor $attribute): Namespace_
    {
        return $class;
    }
}
