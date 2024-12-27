<?php

namespace Tests\src\Builder;

use Darken\Builder\CodeCompiler;
use Tests\TestCase;

class CodeExtractEngineTest extends TestCase
{
    public function testExtractors()
    {
        $config = $this->createConfig();
        $tmpFile = $this->createTmpFile($config, 'extractcompilertest.php', <<<'PHP'
        <?php
        $x = new 
        #[FooBar]
        #[BarFoo(1)]
        #[BarFoo("string")]
        #[BarFoo(["foo1", "foo2"])]
        class {
            #[PrivateTest]
            private $test;

            #[PublicTest]
            public $test2;
        };
        PHP);

        $file = $this->createInputFile($tmpFile);

        $compiler = new CodeCompiler();
        $output = $compiler->compile($file);        

        $this->assertSame(4, count($output->data->getClassAttributes()));
        $this->assertSame(2, count($output->data->getPropertyAttributes()));
    }
}