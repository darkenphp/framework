<?php

declare(strict_types=1);

namespace Darken\Builder\Hooks;

use Darken\Builder\Compiler\Extractor\PropertyAttribute;
use Darken\Enum\HookedAttributeType;
use PhpParser\Node\Stmt\ClassMethod;

abstract class PropertyAttributeHook implements AttributeHookInterface
{
    abstract public function compileConstructorHook(PropertyAttribute $attribute, ClassMethod $constructor): ClassMethod;

    public function attributeType(): HookedAttributeType
    {
        return HookedAttributeType::ON_PROPERTY;
    }
}
