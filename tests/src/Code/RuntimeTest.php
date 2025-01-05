<?php

use Darken\Code\Runtime;
use Tests\TestCase;

class RuntimeTest extends TestCase
{
    public function testInvalidRenderFile()
    {
        $x = new class extends Runtime {
            public function renderFilePath(): string
            {
                return 'invalid';
            }
        };

        $this->expectException(\Exception::class);
        $x->render();
    }

    public function testInvokeString()
    {
        $className = $this->createCompileTest($this->createConfig(), <<<'PHP'
        <?php return new class implements \Darken\Code\InvokeStringInterface{
            public function __invoke() : string
            {
                return 'test';
            }
        };
        PHP, <<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this Do not edit this file. It is auto-generated and changes will be overwritten during the next compile. */ ?><?php

        return new class($this) implements \Darken\Code\InvokeStringInterface
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
        PHP);

        $this->assertSame('test', Runtime::make($className)->render());
    }

    public function testToString()
    {
        $className = $this->createCompileTest($this->createConfig(), <<<'PHP'
        <?php return new class {
            public function __toString() : string
            {
                return 'toString';
            }
        };
        PHP, <<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this Do not edit this file. It is auto-generated and changes will be overwritten during the next compile. */ ?><?php

        return new class($this)
        {
            protected \Darken\Code\Runtime $runtime;
            public function __toString(): string
            {
                return 'toString';
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
        PHP);

        $this->assertSame('toString', Runtime::make($className)->render());
    }

    public function testStringContent()
    {
        $className = $this->createCompileTest($this->createConfig(), <<<'PHP'
        templateString
        PHP, <<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this Do not edit this file. It is auto-generated and changes will be overwritten during the next compile. */ ?>templateString
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
        PHP);

        $this->assertSame('templateString', Runtime::make($className)->render());
    }

    public function testScalarReturn()
    {
        $className = $this->createCompileTest($this->createConfig(), <<<'PHP'
        <?php return 'scalar';
        PHP, <<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this Do not edit this file. It is auto-generated and changes will be overwritten during the next compile. */ ?><?php

        return 'scalar';
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
        PHP);

        $this->assertSame('scalar', Runtime::make($className)->render());
    }

    public function testExceptionInTemplate()
    {
        $className = $this->createCompileTest($this->createConfig(), <<<'PHP'
        <?php throw new \Exception('test');
        PHP, <<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this Do not edit this file. It is auto-generated and changes will be overwritten during the next compile. */ ?><?php
        
        throw new \Exception('test');
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
        PHP);

        $this->expectException(\Exception::class);
        Runtime::make($className)->render();
    }

    public function testExceptionInTemplateWithOutput()
    {
        $className = $this->createCompileTest($this->createConfig(), <<<'PHP'
        <?php 
        echo "This is output before exception.";
        throw new \RuntimeException("Test exception during file execution.");
        PHP, <<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this Do not edit this file. It is auto-generated and changes will be overwritten during the next compile. */ ?><?php
        
        echo "This is output before exception.";
        throw new \RuntimeException("Test exception during file execution.");
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
        PHP);

        $this->expectException(\Exception::class);
        Runtime::make($className)->render();
    }

    public function testExceptionInTemplateWithOutputObEndClean()
    {
        // Define the source code for compilation (the template)
        $sourceCode = <<<'PHP'
        <?php 
        echo "This is output before exception.";
        ob_end_clean();
        throw new \RuntimeException("Test exception during file execution.");
        PHP;

        // Define the compiled code (what the template compiles into)
        $compiledCode = <<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this Do not edit this file. It is auto-generated and changes will be overwritten during the next compile. */ ?><?php

        echo "This is output before exception.";
        ob_end_clean();
        throw new \RuntimeException("Test exception during file execution.");
        PHP;

        // Define the runtime class code
        $runtimeClassCode = <<<'PHP'
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
        PHP;

        // Create the compiled test class
        $className = $this->createCompileTest(
            $this->createConfig(),
            $sourceCode,
            $compiledCode,
            $runtimeClassCode
        );

        $this->expectException(RuntimeException::class);
        Runtime::make($className)->render();
    }
}