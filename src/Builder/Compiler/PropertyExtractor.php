<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler;

use Darken\Attributes\Inject;
use Darken\Attributes\Param;
use Darken\Attributes\RouteParam;
use Darken\Attributes\Slot;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\Array_ as ExprArray_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Property;
use PhpParser\PrettyPrinter\Standard;

class PropertyExtractor
{
    public function __construct(private UseStatementCollector $useStatementCollector, private Property $propertyNode, private PropertyItem $prop, private Attribute $decoratorAttribute)
    {

    }

    public function getVisibility(): string
    {
        if ($this->propertyNode->isPublic()) {
            return 'public';
        } elseif ($this->propertyNode->isProtected()) {
            return 'protected';
        } elseif ($this->propertyNode->isPrivate()) {
            return 'private';
        }
        return 'public'; // Default visibility

    }

    public function getType(): string
    {
        if ($this->propertyNode->type instanceof NullableType) {
            return '?' . $this->propertyNode->type->type->toString();
        } elseif ($this->propertyNode->type instanceof Identifier || $this->propertyNode->type instanceof Name) {
            return $this->propertyNode->type->toString();
        }
        return 'mixed'; // Default type

    }

    public function getName(): string
    {
        return $this->prop->name->toString();
    }

    public function getDefaultValue(): string|null
    {
        if ($this->prop->default !== null) {
            $printer = new Standard();
            $propertyDefaultValue = $this->prop->default;
            if ($propertyDefaultValue instanceof String_) {
                return $printer->prettyPrintExpr($propertyDefaultValue);
            } elseif ($propertyDefaultValue instanceof ExprArray_) {
                return $printer->prettyPrintExpr($propertyDefaultValue);
            }
        }

        return null;
    }

    // #[Inject($this)] <= getDecoratorAttributeParamValue = $this
    public function getDecoratorAttributeParamValue(): string|null
    {
        $dectoratorAttributeFirstArgument = isset($this->decoratorAttribute->args[0]) ? $this->decoratorAttribute->args[0]->value : null;

        if ($dectoratorAttributeFirstArgument instanceof String_) {
            return $dectoratorAttributeFirstArgument->value;
        } elseif ($dectoratorAttributeFirstArgument instanceof ClassConstFetch) {
            return $this->useStatementCollector->ensureClassName($dectoratorAttributeFirstArgument->class->name);
        }

        return null;
    }

    // #[Inject()] <= getDecoratorAttributeName = Inject
    public function getDecoratorAttributeName(): string|false
    {
        if ($this->decoratorAttribute->name instanceof FullyQualified) {
            return $this->decoratorAttribute->name->toString();
        }

        return ltrim($this->useStatementCollector->ensureClassName($this->decoratorAttribute->name->toString()), '\\');
    }

    public function getFunctionNameForRuntimeClass(): string|false
    {
        return match ($this->getDecoratorAttributeName()) {
            RouteParam::class => 'getRouteParam',
            Param::class => 'getArgumentParam',
            Slot::class => 'getSlot',
            Inject::class => 'getContainer',
            default => false,
        };
    }

    public function getArg(): object
    {
        $attributeDecoratorParamValue = isset($this->decoratorAttribute->args[0]) ? $this->decoratorAttribute->args[0]->value : null;


        if ($attributeDecoratorParamValue instanceof String_) {
            $attributeDecoratorParamName = $attributeDecoratorParamValue->value;
            return new String_($attributeDecoratorParamName);

        } elseif ($attributeDecoratorParamValue instanceof ClassConstFetch) {

            $className = $this->useStatementCollector->ensureClassName($attributeDecoratorParamValue->class->name);
            $fullyQualifiedName = new FullyQualified(ltrim($className, '\\'));
            $attributeDecoratorParamName = $className;
            return new ClassConstFetch($fullyQualifiedName, 'class');

        }

        $propertyType = $this->propertyNode->type;
        if ($propertyType instanceof Name) {
            $fullyQualifiedName = new FullyQualified(ltrim($this->useStatementCollector->ensureClassName($propertyType->name), '\\'));
            return new ClassConstFetch($fullyQualifiedName, 'class');
        }

        return new String_($this->prop->name->toString());
    }

    public function getConstructorString(): string
    {
        $propName = $this->getDecoratorAttributeParamValue() ?? $this->getName();
        $string = "{$this->getType()} \${$propName}";

        $defaultValue = $this->getDefaultValue();

        if ($defaultValue !== null) {
            $string .= " = {$defaultValue}";
        }
        return $string;
    }
}
