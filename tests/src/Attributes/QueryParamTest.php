<?php

namespace Tests\src\Attributes;

use Darken\Attributes\Hooks\QueryParamHook;
use Darken\Builder\CodeCompiler;
use Tests\TestCase;

class QueryParamTest extends TestCase
{
    public function testExtractors()
    {
        $config = $this->createConfig();
        $tmpFile = $this->createTmpFile($config, 'extractcompilertest.php', <<<'PHP'
        <?php
        $x = new 
        class {
            #[\Darken\Attributes\QueryParam]
            private $test;

            #[\Darken\Attributes\QueryParam('userId')]
            protected $id;
        };
        PHP);

        $file = $this->createInputFile($tmpFile);

        $compiler = new CodeCompiler();

        $compiler->registerHook(new QueryParamHook());

        $output = $compiler->compile($file);        

        $this->assertSame(<<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this */ ?><?php

        $x = new class($this)
        {
            protected \Darken\Code\Runtime $runtime;
            #[\Darken\Attributes\QueryParam]
            private $test;
            #[\Darken\Attributes\QueryParam('userId')]
            protected $id;
            public function __construct(\Darken\Code\Runtime $runtime)
            {
                $this->runtime = $runtime;
                $this->test = $this->runtime->getQueryParam('test');
                $this->id = $this->runtime->getQueryParam('userId');
            }
        };
        PHP, $output->getCode());
    }
}