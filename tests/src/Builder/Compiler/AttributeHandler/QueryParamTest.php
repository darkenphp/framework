<?php

namespace Tests\src\Builder\Compiler\AttributeHandler;

use Darken\Builder\CodeCompiler;
use Darken\Builder\OutputCompiled;
use Darken\Builder\OutputPolyfill;
use Tests\TestCase;

class QueryParamTest extends TestCase
{
    public function testVerbInjection()
    {
        $this->createCompileTest( <<<'PHP'
        <?php
        $x = new class {
            #[\Darken\Attributes\QueryParam]
            public string $query;
        };
        PHP, <<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this */ ?><?php

        $x = new class($this)
        {
            protected \Darken\Code\Runtime $runtime;
            #[\Darken\Attributes\QueryParam]
            public string $query;
            public function __construct(\Darken\Code\Runtime $runtime)
            {
                $this->runtime = $runtime;
            }
        };
        PHP, <<<'PHP'
        <?php

        namespace Tests\Build\tmp;

        class test extends \Darken\Code\Runtime
        {
            function __construct()
            {
            }
            public function renderFilePath(): string
            {
                return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'test.compiled.php';
            }
        }
        PHP);
    }
}