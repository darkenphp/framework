<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler;

use Darken\Builder\Compiler\Extractor\ClassAttribute;
use Darken\Builder\Compiler\Extractor\PropertyAttribute;
use Darken\Builder\Hooks\AttributeHookInterface;
use Darken\Builder\Hooks\PropertyAttributeHook;
use Darken\Builder\OutputPage;
use Darken\Enum\HookedAttributeType;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use Yiisoft\Arrays\ArrayHelper;

class DataExtractorVisitor extends NodeVisitorAbstract
{
    private array $propertyAttributes = [];

    private array $classAttributes = [];

    /**
     * @param array<AttributeHookInterface> $hooks
     */
    public function __construct(private UseStatementCollector $useStatementCollector, private array $hooks)
    {
    }

    public function addPropertyAttribute(PropertyAttribute $propertyAttribute): void
    {
        $this->propertyAttributes[] = $propertyAttribute;
    }

    public function getPropertyAttributes(): array
    {
        return $this->propertyAttributes;
    }

    public function addClassAttribute(ClassAttribute $classAttribute): void
    {
        $this->classAttributes[] = $classAttribute;
    }

    public function getClassAttributes(): array
    {
        return $this->classAttributes;
    }

    public function onCompileConstructorHook(ClassMethod $constructor): ClassMethod
    {
        foreach ($this->propertyAttributes as $propertyAttribute) {
            $hooks = $this->getHooksByType(HookedAttributeType::ON_PROPERTY);
            foreach ($hooks as $hook) {
                /** @var PropertyAttributeHook $hook */
                if ($hook->isValidAttribute($propertyAttribute)) {
                    $constructor = $hook->compileConstructorHook($propertyAttribute, $constructor);
                }
            }
        }
        foreach ($this->classAttributes as $classAttribute) {
            $hooks = $this->getHooksByType(HookedAttributeType::ON_CLASS);
            foreach ($hooks as $hook) {
                /** @var PropertyAttributeHook $hook */
                if ($hook->isValidAttribute($classAttribute)) {
                    $constructor = $hook->compileConstructorHook($classAttribute, $constructor);
                }
            }
        }
        return $constructor;
    }

    public function onPolyfillConstructorHook(Method $constructor): Method
    {
        foreach ($this->propertyAttributes as $propertyAttribute) {
            $hooks = $this->getHooksByType(HookedAttributeType::ON_PROPERTY);
            foreach ($hooks as $hook) {
                /** @var PropertyAttributeHook $hook */
                if ($hook->isValidAttribute($propertyAttribute)) {
                    $constructor = $hook->polyfillConstructorHook($propertyAttribute, $constructor);
                }
            }
        }
        foreach ($this->classAttributes as $classAttribute) {
            $hooks = $this->getHooksByType(HookedAttributeType::ON_CLASS);
            foreach ($hooks as $hook) {
                /** @var PropertyAttributeHook $hook */
                if ($hook->isValidAttribute($classAttribute)) {
                    $constructor = $hook->polyfillConstructorHook($classAttribute, $constructor);
                }
            }
        }
        return $constructor;
    }

    public function onPolyfillClassHook(Class_ $builder): Class_
    {
        foreach ($this->propertyAttributes as $propertyAttribute) {
            $hooks = $this->getHooksByType(HookedAttributeType::ON_PROPERTY);
            foreach ($hooks as $hook) {
                /** @var PropertyAttributeHook $hook */
                if ($hook->isValidAttribute($propertyAttribute)) {
                    $builder = $hook->polyfillClassHook($propertyAttribute, $builder);
                }
            }
        }
        foreach ($this->classAttributes as $classAttribute) {
            $hooks = $this->getHooksByType(HookedAttributeType::ON_CLASS);
            foreach ($hooks as $hook) {
                /** @var PropertyAttributeHook $hook */
                if ($hook->isValidAttribute($classAttribute)) {
                    $builder = $hook->polyfillClassHook($classAttribute, $builder);
                }
            }
        }
        return $builder;
    }

    public function onPageDataHook(OutputPage $page): array
    {
        $data = [];
        foreach ($this->propertyAttributes as $propertyAttribute) {
            $hooks = $this->getHooksByType(HookedAttributeType::ON_PROPERTY);
            foreach ($hooks as $hook) {
                /** @var PropertyAttributeHook $hook */
                if ($hook->isValidAttribute($propertyAttribute)) {
                    $data = ArrayHelper::merge($data, $hook->pageDataHook($propertyAttribute, $page));
                }
            }
        }
        foreach ($this->classAttributes as $classAttribute) {
            $hooks = $this->getHooksByType(HookedAttributeType::ON_CLASS);
            foreach ($hooks as $hook) {
                /** @var PropertyAttributeHook $hook */
                if ($hook->isValidAttribute($classAttribute)) {
                    $data = ArrayHelper::merge($data, $hook->pageDataHook($classAttribute, $page));
                }
            }
        }
        return $data;
    }

    public function enterNode(Node $node)
    {
        // Check if the node is a class (including anonymous classes)
        if ($node instanceof ClassLike) {
            // Iterate through attribute groups
            foreach ($node->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attribute) {
                    $this->addClassAttribute(new ClassAttribute($this->useStatementCollector, $attribute));
                }
            }
        }

        return null;
    }

    private function getHooksByType(HookedAttributeType $type): array
    {
        return array_filter($this->hooks, fn (AttributeHookInterface $hook) => $hook->attributeType() === $type);
    }
}
