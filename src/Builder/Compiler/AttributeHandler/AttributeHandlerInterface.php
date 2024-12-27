<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler\AttributeHandler;

use Darken\Builder\Compiler\AttributeExtractor;
use Darken\Builder\Compiler\AttributeInterface;
use PhpParser\Builder\Namespace_;
use PhpParser\Node\Stmt\ClassMethod;

interface AttributeHandlerInterface
{
    public function isAttributeAccepted(AttributeInterface $attribute): bool;

    public function compileConstructorHook(ClassMethod $method, AttributeExtractor $attribute): ClassMethod;

    public function polyfillCreatorHook(Namespace_ $class, AttributeExtractor $attribute): Namespace_;

    public function polyfillConstructorHook(Namespace_ $method, AttributeExtractor $attribute): Namespace_;
}
