<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler;

use Darken\Attributes\Inject;
use Darken\Attributes\Param as AttributesParam;
use Darken\Attributes\RouteParam;
use Darken\Attributes\Slot;
use PhpParser\Builder\Property;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
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

            // Collect properties that have the RouteParam attribute
            foreach ($node->getProperties() as $propertyNode) {
                foreach ($propertyNode->props as $prop) {
                    foreach ($propertyNode->attrGroups as $attrGroup) {
                        foreach ($attrGroup->attrs as $attr) {

                            if ($attr->name instanceof FullyQualified) {
                                $attrName = $attr->name->toString();
                            } else {
                                $attrName = $this->useStatementCollector->ensureClassName($attr->name->toString());
                            }

                            $attrName = ltrim($attrName, '\\');

                            /** @var PropertyItem $prop */
                            if (in_array($attrName, [RouteParam::class, AttributesParam::class, Slot::class, Inject::class])) {

                                $this->dataExtractorVisitor->addProperty(new PropertyExtractor($this->useStatementCollector, $propertyNode, $prop, $attr));
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
            foreach ($constructor->params as $paramIndex => $param) {
                if ($param->var instanceof Variable && $param->var->name === 'runtime') {
                    $hasContextParam = $paramIndex;
                    break;
                }
            }

            $contextParam = new Param(
                var: new Variable('runtime'),
                default: null,
                type: new FullyQualified('Darken\\Code\\Runtime')
            );
            if ($hasContextParam === false) {
                $constructor->params[] = $contextParam;
            } else {
                $constructor->params[$hasContextParam] = $contextParam;
            }

            foreach ($constructor->stmts as $index => $stmt) {
                /** @var Expression $stmt */
                if ($stmt->expr instanceof Assign && $stmt->expr->var instanceof PropertyFetch) {
                    if ($stmt->expr->var->name->name == 'runtime') {
                        unset($constructor->stmts[$index]);
                    }
                }
            }

            // Add $this->runtime = $runtime; in the constructor body if not present
            $constructor->stmts[] = new Expression(
                new Assign(
                    new PropertyFetch(new Variable('this'), 'runtime'),
                    new Variable('runtime')
                )
            );

            foreach ($this->dataExtractorVisitor->getProperties() as $property) {
                /** @var PropertyExtractor $property */
                $getterName = $property->getFunctionNameForRuntimeClass();

                if (!$getterName) {
                    continue;
                }

                $assignment = new Expression(
                    new Assign(
                        new PropertyFetch(new Variable('this'), $property->getName()),
                        new MethodCall(
                            new PropertyFetch(new Variable('this'), 'runtime'),
                            $getterName,
                            [new Arg($property->getArg())]
                        )
                    )
                );
                $constructor->stmts[] = $assignment;

            }
        }

        return null;
    }
}
