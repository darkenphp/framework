<?php

declare(strict_types=1);

namespace Darken\Builder\Hooks;

use Darken\Builder\Compiler\Extractor\ClassAttribute;
use Darken\Builder\OutputPage;
use Darken\Enum\HookedAttributeType;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\Node\Stmt\ClassMethod;

abstract class ClassAttributeHook implements AttributeHookInterface
{
    use HookHelperTrait;

    abstract public function compileConstructorHook(ClassAttribute $attribute, ClassMethod $constructor): ClassMethod;

    abstract public function polyfillConstructorHook(ClassAttribute $attribute, Method $constructor): Method;

    abstract public function polyfillClassHook(ClassAttribute $attribute, Class_ $builder): Class_;

    abstract public function pageDataHook(ClassAttribute $attribute, OutputPage $page): array;

    public function attributeType(): HookedAttributeType
    {
        return HookedAttributeType::ON_CLASS;
    }
}
