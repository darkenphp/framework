<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;

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

    public function getDefaultValue(): Node|null
    {
        if ($this->prop->default !== null) {
            return $this->prop->default;
        }

        return null;
    }

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

    public function getDecoratorAttributeName(): string|false
    {
        if ($this->decoratorAttribute->name instanceof FullyQualified) {
            return $this->decoratorAttribute->name->toString();
        }

        return ltrim($this->useStatementCollector->ensureClassName($this->decoratorAttribute->name->toString()), '\\');
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

    public function createGetDataExpression(string $getterName): Expression
    {
        return new Expression(
            new Assign(
                new PropertyFetch(new Variable('this'), $this->getName()),
                new MethodCall(
                    new PropertyFetch(new Variable('this'), 'runtime'),
                    $getterName,
                    [new Arg($this->getArg())]
                )
            )
        );
    }
}
