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
        use Darken\Attributes\QueryParam;
        $x = new 
        class {
            #[\Darken\Attributes\QueryParam]
            private $test;

            #[\Darken\Attributes\QueryParam('userId')]
            protected $id;

            #[QueryParam]
            public $test2;
        };
        PHP);

        $file = $this->createInputFile($tmpFile);

        $compiler = new CodeCompiler();

        $output = $compiler->compile($file);        

        $this->assertSame(<<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this */ ?><?php

        use Darken\Attributes\QueryParam;
        $x = new class($this)
        {
            protected \Darken\Code\Runtime $runtime;
            #[\Darken\Attributes\QueryParam]
            private $test;
            #[\Darken\Attributes\QueryParam('userId')]
            protected $id;
            #[QueryParam]
            public $test2;
            public function __construct(\Darken\Code\Runtime $runtime)
            {
                $this->runtime = $runtime;
                $this->test = $this->runtime->getQueryParam('test');
                $this->id = $this->runtime->getQueryParam('userId');
                $this->test2 = $this->runtime->getQueryParam('test2');
            }
        };
        PHP, $output->getCode());
    }

    public function testQueryParamPolyfill()
    {
        $className = $this->createCompileTest($this->createConfig(), <<<'PHP'
        <?php
        use Darken\Attributes\QueryParam;
        $x = new class {
            #[\Darken\Attributes\QueryParam]
            public $test;

            #[\Darken\Attributes\QueryParam('userId')]
            protected $id;

            #[QueryParam]
            public $test2;
        };
        var_dump($x->test);
        PHP, <<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this */ ?><?php

        use Darken\Attributes\QueryParam;
        $x = new class($this)
        {
            protected \Darken\Code\Runtime $runtime;
            #[\Darken\Attributes\QueryParam]
            public $test;
            #[\Darken\Attributes\QueryParam('userId')]
            protected $id;
            #[QueryParam]
            public $test2;
            public function __construct(\Darken\Code\Runtime $runtime)
            {
                $this->runtime = $runtime;
                $this->test = $this->runtime->getQueryParam('test');
                $this->id = $this->runtime->getQueryParam('userId');
                $this->test2 = $this->runtime->getQueryParam('test2');
            }
        };
        var_dump($x->test);
        PHP, <<<'PHP'
        <?php

        namespace Tests\Build\data\generated;

        class test extends \Darken\Code\Runtime
        {
            public function __construct()
            {
            }
            public function renderFilePath(): string
            {
                return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'test.compiled.php';
            }
        }
        PHP
        );

        $this->mockWebAppRequest($this->createConfig(), 'GET', '/?test=test&userId=1&test2=test');

        $runtime = $this->createRuntime($className);

        $this->assertSame('string(4) "test"
', $runtime->render());
    }
}