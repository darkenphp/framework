<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler;

use Darken\Attributes\Inject;
use Darken\Attributes\Param as AttributesParam;
use Darken\Attributes\RouteParam;
use Darken\Attributes\Slot;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\NodeVisitorAbstract;

class GlobalVisitor extends NodeVisitorAbstract
{
    public function __construct(private UseStatementCollector $useStatementCollector, private DataExtractorVisitor $dataExtractorVisitor)
    {
    }

    public function enterNode(Node $node)
    {
        // If it's an anonymous class instantiation (e.g. $class = new class(...) {...}; )
        // we add $this as a constructor argument.
        if ($node instanceof New_ && $node->class instanceof Class_ && $node->class->name === null) {
            // Add $this as an argument to the anonymous class instantiation
            $node->args[] = new Arg(new Variable('this'));
        }

        // Check if this node is a class (including anonymous classes)
        if ($node instanceof ClassLike) {

            /**
             * @var array<int, array{
             *     attrName: class-string,
             *     propertyName: string,
             *     paramName: string,
             *     paramType: 'string'|'int'|'bool',
             *     arg: \PhpParser\Node\Expr\ClassConstFetch
             * }> $queryParams
             */
            $queryParams = [];

            // Collect properties that have the RouteParam attribute
            foreach ($node->getProperties() as $propertyNode) {
                foreach ($propertyNode->props as $prop) {
                    foreach ($propertyNode->attrGroups as $attrGroup) {
                        foreach ($attrGroup->attrs as $attr) {
                            $attrName = ltrim($attr->name->toString(), '\\');

                            /** @var PropertyItem $prop */

                            if (in_array($attrName, [RouteParam::class, AttributesParam::class, Slot::class, Inject::class])) {
                                // Extract the parameter name from the attribute arguments
                                // if (isset($attr->args[0]) && $attr->args[0]->value instanceof String_) {
                                $attributeDecoratorParamValue = isset($attr->args[0]) ? $attr->args[0]->value : null;

                                if ($attributeDecoratorParamValue instanceof String_) {
                                    $paramName = $attributeDecoratorParamValue->value;
                                    $queryParams[] = [
                                        'attrName' => $attrName,
                                        'propertyName' => $prop->name->toString(),
                                        'paramName' => $paramName,
                                        'paramType' => 'string', // adjust, sould return int, bool, string
                                        'arg' => new String_($paramName),
                                    ];
                                } elseif ($attributeDecoratorParamValue instanceof ClassConstFetch) {

                                    $className = $this->useStatementCollector->ensureClassName($attributeDecoratorParamValue->class->name);
                                    $fullyQualifiedName = new FullyQualified(ltrim($className, '\\'));

                                    $queryParams[] = [
                                        'attrName' => $attrName,
                                        'propertyName' => $prop->name->toString(),
                                        'paramName' => $className,
                                        'paramType' => 'string', // adjust, sould return int, bool, strin
                                        'arg' => new ClassConstFetch($fullyQualifiedName, 'class'),
                                    ];

                                } else {
                                    $propertyType = $propertyNode->type;
                                    // if this is a class:
                                    if ($propertyType instanceof Name || $propertyType instanceof FullyQualified) {
                                        $fullyQualifiedName = new FullyQualified(ltrim($this->useStatementCollector->ensureClassName($propertyType->name), '\\'));
                                        $arg = new ClassConstFetch($fullyQualifiedName, 'class');
                                    } else {
                                        $arg = new String_($prop->name->toString());
                                    }

                                    // get the property type
                                    $queryParams[] = [
                                        'attrName' => $attrName,
                                        'propertyName' => $prop->name->toString(),
                                        'paramName' => $prop->name->toString(),
                                        'paramType' => 'string', // adjust, sould return int, bool, string
                                        'arg' => $arg,
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            // Ensure protected Runtime $runtime property exists
            $hasContextProperty = false;
            foreach ($node->getProperties() as $propertyNode) {
                foreach ($propertyNode->props as $prop) {
                    if ($prop->name->toString() === 'runtime') {
                        $hasContextProperty = true;
                        break 2;
                    }
                }
            }

            if (!$hasContextProperty) {
                // Add "protected Darken\Build\Runtime $context;" property
                $contextProperty = new PropertyNode(
                    Modifiers::PROTECTED,
                    [new PropertyItem('runtime')],
                    [],
                    new FullyQualified('Darken\\Code\\Runtime')
                );
                array_unshift($node->stmts, $contextProperty);
            }


            // Ensure there is a constructor
            $constructor = $node->getMethod('__construct');
            if (!$constructor) {
                $constructor = new ClassMethod(
                    '__construct',
                    [
                        'flags' => Modifiers::PUBLIC,
                        'params' => [],
                        'stmts' => [],
                    ]
                );
                $node->stmts[] = $constructor;
            }

            // Ensure the constructor has a Darken\Page\Runtime $context parameter
            $hasContextParam = false;
            foreach ($constructor->params as $param) {
                if ($param->var instanceof Variable && $param->var->name === 'runtime') {
                    $hasContextParam = true;
                    break;
                }
            }

            if (!$hasContextParam) {
                $contextParam = new Param(
                    var: new Variable('runtime'),
                    default: null,
                    type: new FullyQualified('Darken\\Code\\Runtime')
                );
                $constructor->params[] = $contextParam;

                // Add $this->runtime = $runtime; in the constructor body if not present
                $constructor->stmts[] = new Expression(
                    new Assign(
                        new PropertyFetch(new Variable('this'), 'runtime'),
                        new Variable('runtime')
                    )
                );
            }

            // If QueryParams found, add the assignments to constructor
            if (!empty($queryParams)) {
                foreach ($queryParams as $qp) {

                    $getterName = match ($qp['attrName']) {
                        RouteParam::class => 'getRouteParam',
                        AttributesParam::class => 'getArgumentParam',
                        Slot::class => 'getSlot',
                        Inject::class => 'getContainer',
                        default => false,
                    };

                    if (!$getterName) {
                        continue;
                    }

                    switch ($qp['attrName']) {
                        case RouteParam::class:
                            $this->dataExtractorVisitor->addData('constructor', $qp);
                            break;
                        case AttributesParam::class:
                            $this->dataExtractorVisitor->addData('constructor', $qp);
                            break;
                        case Slot::class:
                            $this->dataExtractorVisitor->addData('slots', $qp);
                            break;
                    }

                    $assignment = new Expression(
                        new Assign(
                            new PropertyFetch(new Variable('this'), $qp['propertyName']),
                            new MethodCall(
                                new PropertyFetch(new Variable('this'), 'runtime'),
                                $getterName,
                                [new Arg($qp['arg'])]
                            )
                        )
                    );
                    $constructor->stmts[] = $assignment;
                }
            }
        }

        return null;
    }
}
