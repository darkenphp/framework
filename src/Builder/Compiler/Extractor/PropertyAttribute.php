<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler\Extractor;

use Darken\Attributes\ConstructorParam;
use Darken\Attributes\Inject;
use Darken\Attributes\RouteParam;
use Darken\Attributes\Slot;
use Darken\Builder\Compiler\UseStatementCollector;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
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

class PropertyAttribute implements AttributeExtractorInterface
{
    use AttributeExtractorTrait;

    public function __construct(public UseStatementCollector $useStatementCollector, public Property $propertyNode, public PropertyItem $prop, private Attribute $decoratorAttribute)
    {

    }

    public function getDecoratorFirstArgument(): Expr|null
    {
        return $this->createDecoratorAttributeFirstArgument($this->decoratorAttribute);
    }

    public function getDecoratorAttributeArguments(): array
    {
        return $this->createDecoratorAttributeArguments($this->decoratorAttribute);
    }

    // #[Inject($this)] <= getDecoratorAttributeParamValue = $this
    public function getDecoratorAttributeParamValue(): string|null|array
    {
        return $this->createDecoratorAttributeParamValue($this->useStatementCollector, $this->decoratorAttribute);
    }

    // #[Inject()] <= getDecoratorAttributeName = Inject
    public function getDecoratorAttributeName(): string|false
    {
        return $this->createDecoratorAttributeParamName($this->useStatementCollector, $this->decoratorAttribute);
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

    public function getFunctionNameForRuntimeClass(): string|false
    {
        // not needed in future
        return match ($this->getDecoratorAttributeName()) {
            RouteParam::class => 'getRouteParam',
            ConstructorParam::class => 'getArgumentParam',
            Slot::class => 'getSlot',
            Inject::class => 'getContainer',
            default => false,
        };
    }

    public function getArg(): object
    {
        $attributeDecoratorParamValue = $this->getDecoratorFirstArgument();


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

    public function createAssignExpression(string $getterName): Expression
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
