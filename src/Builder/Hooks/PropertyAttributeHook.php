<?php

declare(strict_types=1);

namespace Darken\Builder\Hooks;

use Darken\Builder\Compiler\Extractor\PropertyAttribute;
use Darken\Enum\HookedAttributeType;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\Node\Stmt\ClassMethod;

abstract class PropertyAttributeHook implements AttributeHookInterface
{
    use HookHelperTrait;

    abstract public function compileConstructorHook(PropertyAttribute $attribute, ClassMethod $constructor): ClassMethod;

    abstract public function polyfillConstructorHook(PropertyAttribute $attribute, Method $constructor): Method;

    abstract public function polyfillClassHook(PropertyAttribute $attribute, Class_ $builder): Class_;

    public function attributeType(): HookedAttributeType
    {
        return HookedAttributeType::ON_PROPERTY;
    }
}
