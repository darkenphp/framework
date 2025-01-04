<?php

namespace Tests\src\Attributes;

use Darken\Attributes\Middleware;
use Darken\Attributes\Hooks\MiddlewareHook;
use Darken\Builder\Compiler\Extractor\AttributeExtractorInterface;
use Darken\Builder\Compiler\Extractor\ClassAttribute;
use Darken\Builder\Compiler\UseStatementCollector;
use Darken\Builder\OutputPage;
use InvalidArgumentException;
use PhpParser\BuilderFactory;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\ClassMethod;
use Tests\TestCase;

class MiddlewareHookTest extends TestCase
{
    /**
     * Test middleware attribute with ClassConstFetch for class, Array_ for params, and String_ for position.
     */
    public function testParseMiddlewareAttribute_WithClassConstFetchArrayAndString()
    {
        $attribute = new Attribute(new Name(Middleware::class), [
                new ArrayItem(new ClassConstFetch(new Name('Resolved\\MiddlewareClass'), 'middlwareclass'), new String_('class')),
                new ArrayItem(new Array_([
                    new ArrayItem(new String_('param1'), new String_('key1')),
                    new ArrayItem(new Int_(42), new String_('key2')),
                ]), new String_('params')),
                new ArrayItem(new String_('before'), new String_('position')),
        ]);

        $classAttributeMock = new ClassAttribute(new UseStatementCollector, $attribute);

        $this->assertSame(Middleware::class, $classAttributeMock->getDecoratorAttributeName());
        $pageData = (new MiddlewareHook())->pageDataHook($classAttributeMock, $this->createMock(OutputPage::class));

        // Define expected result
        $expected = [
            'middlewares' => [
                [
                    'class'    => '\Resolved\\MiddlewareClass',
                    'params'   => [
                        'key1' => 'param1',
                        'key2' => 42,
                    ],
                    'position' => 'before',
                ]
            ]
        ];

        // Assert the result matches expectation
        $this->assertEquals($expected, $pageData);
    }

}
