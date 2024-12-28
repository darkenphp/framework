<?php

namespace Tests\src\Attributes;

use Darken\Attributes\Hooks\ConstructorParamHook;
use Darken\Builder\CodeCompiler;
use Darken\Builder\OutputCompiled;
use Darken\Builder\OutputPolyfill;
use Tests\TestCase;

class ConstructorParamTest extends TestCase
{
    public function testExtractors()
    {
        $config = $this->createConfig();
        $tmpFile = $this->createTmpFile($config, 'extractcompilertest.php', <<<'PHP'
        <?php
        $x = new 
        class {
            #[\Darken\Attributes\ConstructorParam]
            private $test;

            #[\Darken\Attributes\ConstructorParam('userId')]
            protected $id;
        };
        PHP);

        $file = $this->createInputFile($tmpFile);

        $compiler = new CodeCompiler();

        $output = $compiler->compile($file);        

        $this->assertSame(<<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this */ ?><?php

        $x = new class($this)
        {
            protected \Darken\Code\Runtime $runtime;
            #[\Darken\Attributes\ConstructorParam]
            private $test;
            #[\Darken\Attributes\ConstructorParam('userId')]
            protected $id;
            public function __construct(\Darken\Code\Runtime $runtime)
            {
                $this->runtime = $runtime;
                $this->test = $this->runtime->getArgumentParam('test');
                $this->id = $this->runtime->getArgumentParam('userId');
            }
        };
        PHP, $output->getCode());

        $outputCompiled = new OutputCompiled($output->getCode(), $file, $config);

        $polyfill = new OutputPolyfill($outputCompiled, $output);

        $polycontent = $polyfill->getBuildOutputContent();

        $this->assertSame(<<<'PHP'
        <?php

        namespace Tests\Build\data\generated;

        class extractcompilertest extends \Darken\Code\Runtime
        {
            public function __construct(mixed $test, mixed $userId)
            {
                $this->setArgumentParam('test', $test);
                $this->setArgumentParam('userId', $userId);
            }
            public function renderFilePath(): string
            {
                return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'extractcompilertest.compiled.php';
            }
        }
        PHP, $polycontent);

    }
}