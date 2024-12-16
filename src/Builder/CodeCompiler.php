<?php

declare(strict_types=1);

namespace Darken\Builder;

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
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class CodeCompiler
{
    public function compile(InputFile $file): string
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $ast = $parser->parse($file->getContent());
        $traverser = new NodeTraverser();
        $visitor = new class() extends NodeVisitorAbstract {
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
                    $queryParams = [];

                    // Collect properties that have the RouteParam attribute
                    foreach ($node->getProperties() as $propertyNode) {
                        foreach ($propertyNode->props as $prop) {
                            foreach ($propertyNode->attrGroups as $attrGroup) {
                                foreach ($attrGroup->attrs as $attr) {
                                    $attrName = $attr->name->toString();
                                    if ($attrName === 'Darken\Attributes\RouteParam' || $attrName === '\Darken\Attributes\RouteParam') {
                                        // Extract the parameter name from the attribute arguments
                                        if (isset($attr->args[0]) && $attr->args[0]->value instanceof String_) {
                                            $paramName = $attr->args[0]->value->value;
                                            $queryParams[] = [
                                                'propertyName' => $prop->name->toString(),
                                                'paramName' => $paramName,
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
                            $assignment = new Expression(
                                new Assign(
                                    new PropertyFetch(new Variable('this'), $qp['propertyName']),
                                    new MethodCall(
                                        new PropertyFetch(new Variable('this'), 'runtime'),
                                        'getRouteParam',
                                        [new Arg(new String_($qp['paramName']))]
                                    )
                                )
                            );
                            $constructor->stmts[] = $assignment;
                        }
                    }
                }
            }
        };

        $traverser->addVisitor(new $visitor());
        $ast = $traverser->traverse($ast);

        // Pretty print the modified AST
        $prettyPrinter = new Standard();
        return '<?php /** @var \Darken\Code\Runtime $this */ ?>' . $prettyPrinter->prettyPrintFile($ast);
    }
}
