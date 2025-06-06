<?php

namespace Tests\src\Attributes;

use Darken\Attributes\HttpMethod;
use Darken\Attributes\QueryParam;
use Darken\Builder\CodeCompiler;
use Tests\TestCase;

class HttpMethodTest extends TestCase
{
    public function testConstructor()
    {
        $mid = new HttpMethod('get,post');
        $this->assertInstanceOf(HttpMethod::class, $mid);
    }
    
    public function testExtractors()
    {
        $config = $this->createConfig();
        $tmpFile = $this->createTmpFile($config, 'extractcompilertest.php', <<<'PHP'
        <?php
        return new
        #[Darken\Attributes\HttpMethod('get,post')]
        class {
        };
        PHP);

        $file = $this->createInputFile($tmpFile);

        $compiler = new CodeCompiler();

        $output = $compiler->compile($file);        

        $this->assertSame(<<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this Do not edit this file. It is auto-generated and changes will be overwritten during the next compile. */ ?><?php

        return new #[Darken\Attributes\HttpMethod('get,post')] class($this)
        {
            protected \Darken\Code\Runtime $runtime;
            public function __construct(\Darken\Code\Runtime $runtime)
            {
                $this->runtime = $runtime;
            }
        };
        PHP, $output->getCode());
    }

    public function testQueryParamPolyfill()
    {
        $className = $this->createCompileTest($this->createConfig(), <<<'PHP'
        <?php
        return new
        #[Darken\Attributes\HttpMethod(['get', 'post'])]
        class implements \Darken\Code\InvokeStringInterface {
            public function __invoke():string
            {
                return 'test';
            }
        };
        PHP, <<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this Do not edit this file. It is auto-generated and changes will be overwritten during the next compile. */ ?><?php

        return new #[Darken\Attributes\HttpMethod(['get', 'post'])] class($this) implements \Darken\Code\InvokeStringInterface
        {
            protected \Darken\Code\Runtime $runtime;
            public function __invoke(): string
            {
                return 'test';
            }
            public function __construct(\Darken\Code\Runtime $runtime)
            {
                $this->runtime = $runtime;
            }
        };
        PHP, <<<'PHP'
        <?php /** Do not edit this file. It is auto-generated and changes will be overwritten during the next compile. */ ?><?php

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
    }
}