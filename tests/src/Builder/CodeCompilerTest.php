<?php

namespace Tests\src\Builder;

use PhpParser\Node\Scalar\String_;
use Darken\Builder\CodeCompiler;
use Darken\Builder\Compiler\PropertyExtractor;
use Darken\Builder\OutputCompiled;
use Darken\Builder\OutputPolyfill;
use PhpParser\Error;
use RuntimeException;
use Tests\TestCase;

class CodeCompilerTest extends TestCase
{
    public function testLayoutFile()
    {
        $inputFile = $this->createInputFile(__DIR__ . '/../../data/components/Layout1.php');

        $compiler = new CodeCompiler();
        $output = $compiler->compile($inputFile);

        $this->assertSame(
<<<'PHP'
<?php /** @var \Darken\Code\Runtime $this */ ?><?php

use Tests\data\di\Db;
$class = new class($this)
{
    protected \Darken\Code\Runtime $runtime;
    #[\Darken\Attributes\Param]
    public string $arg1;
    #[\Darken\Attributes\Param('nmdArgu2')]
    public string $namedArg2;
    #[\Darken\Attributes\Slot]
    public string $slot1;
    #[\Darken\Attributes\Slot('nmdSlot2')]
    public string $slot2;
    #[\Darken\Attributes\Inject]
    public Db $db1;
    #[\Darken\Attributes\Inject]
    public \Tests\data\di\Db $db2;
    #[\Darken\Attributes\Inject(Db::class)]
    public Db $db3;
    public function upperArg1(): string
    {
        return strtoupper($this->arg1);
    }
    public function __construct(\Darken\Code\Runtime $runtime)
    {
        $this->runtime = $runtime;
        $this->arg1 = $this->runtime->getArgumentParam('arg1');
        $this->namedArg2 = $this->runtime->getArgumentParam('nmdArgu2');
        $this->slot1 = $this->runtime->getSlot('slot1');
        $this->slot2 = $this->runtime->getSlot('nmdSlot2');
        $this->db1 = $this->runtime->getContainer(\Tests\data\di\Db::class);
        $this->db2 = $this->runtime->getContainer(\Tests\data\di\Db::class);
        $this->db3 = $this->runtime->getContainer(\Tests\data\di\Db::class);
    }
};
?>
<h1><?php 
echo $class->arg1;
?></h1>
<h1><?php 
echo $class->upperArg1();
?></h1>
<h2><?php 
echo $class->namedArg2;
?></h2>
<div><?php 
echo $class->slot1;
?></div>
<div><?php 
echo $class->slot2;
?></div>
<div><?php 
echo $class->db1->getUpperDsn();
?></div>
<div><?php 
echo $class->db2->getUpperDsn();
?></div>
<div><?php 
echo $class->db3->getUpperDsn();
?></div>
PHP, $output->getCode());

        // Assert that 'middlewares' is an empty array
        $this->assertSame([], $output->getMeta('middlewares'), "Middlewares should be an empty array.");

        /** @var PropertyExtractor $constructor1 */
        $constructor1 = $output->getMeta('constructor')[0];
        $this->assertSame('arg1', $constructor1->getName());
        $this->assertSame('string', $constructor1->getType());
        $this->assertSame(null, $constructor1->getDefaultValue());
        $this->assertInstanceOf(String_::class, $constructor1->getArg());
        $this->assertSame('Darken\Attributes\Param', $constructor1->getDecoratorAttributeName());
        $this->assertSame(null, $constructor1->getDecoratorAttributeParamValue());
        
        $constructor2 = $output->getMeta('constructor')[1];
        $this->assertSame('namedArg2', $constructor2->getName());
        $this->assertSame('string', $constructor2->getType());
        $this->assertSame(null, $constructor2->getDefaultValue());
        $this->assertInstanceOf(String_::class, $constructor2->getArg());
        $this->assertSame('Darken\Attributes\Param', $constructor2->getDecoratorAttributeName());
        $this->assertSame('nmdArgu2', $constructor2->getDecoratorAttributeParamValue());

        $slot1 = $output->getMeta('slots')[0];
        $this->assertSame('slot1', $slot1->getName());
        $this->assertSame('string', $slot1->getType());
        $this->assertSame(null, $slot1->getDefaultValue());
        $this->assertInstanceOf(String_::class, $slot1->getArg());
        $this->assertSame('Darken\Attributes\Slot', $slot1->getDecoratorAttributeName());
        $this->assertSame(null, $slot1->getDecoratorAttributeParamValue());

        $slot2 = $output->getMeta('slots')[1];
        $this->assertSame('slot2', $slot2->getName());
        $this->assertSame('string', $slot2->getType());
        $this->assertSame(null, $slot2->getDefaultValue());
        $this->assertInstanceOf(String_::class, $slot2->getArg());
        $this->assertSame('Darken\Attributes\Slot', $slot2->getDecoratorAttributeName());
        $this->assertSame('nmdSlot2', $slot2->getDecoratorAttributeParamValue());


        $outputCompiled = new OutputCompiled($output->getCode(), $inputFile, $this->createConfig());
        $this->assertStringContainsString('tests/.build/data/components/Layout1.compiled.php', $outputCompiled->getBuildOutputFilePath());
        $this->assertSame('/data/components', $outputCompiled->getRelativeDirectory());

        $polyfill = new OutputPolyfill($outputCompiled, $output);
        $this->assertStringContainsString('tests/.build/data/components/Layout1.php', $polyfill->getBuildOutputFilePath());
        $this->assertSame('Tests\Build\data\components\Layout1', $polyfill->getFullQualifiedClassName());

        $this->assertSame(
<<<'PHP'
<?php
namespace Tests\Build\data\components;

class Layout1 extends \Darken\Code\Runtime
{
    public function __construct(string $arg1, string $nmdArgu2)
    {
        $this->setArgumentParam("arg1", $arg1);
        $this->setArgumentParam("nmdArgu2", $nmdArgu2);
    }

        public function openSlot1() : self
    {
        ob_start();
        return $this;
    }

    public function closeSlot1() : self
    {
        $this->setSlot('slot1', ob_get_clean());
        return $this;
    }

    public function openNmdSlot2() : self
    {
        ob_start();
        return $this;
    }

    public function closeNmdSlot2() : self
    {
        $this->setSlot('nmdSlot2', ob_get_clean());
        return $this;
    }

    public function renderFilePath(): string
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR  . 'Layout1.compiled.php';
    }
}
PHP, $polyfill->getBuildOutputContent()
        );
    }

    public function testCompilerException()
    {
        $tmpFile = $this->createTmpFile('test.php', <<<'PHP'
        <?php
        use \Darken\Attributes\Param;
        $x = new class {

            #[Param]
            public string $stringvar = 'test'
        };
        PHP);

        $file = $this->createInputFile($tmpFile);

        $compiler = new CodeCompiler();
        $this->expectException(RuntimeException::class);
        $compiler->compile($file);
    }

    public function testAlreadyDefinedConstructorWithRuntimeParam()
    {
        $tmpFile = $this->createTmpFile('test.php', <<<'PHP'
        <?php
        use \Darken\Attributes\Param;
        $x = new class {

            public $runtime;

            #[DoesNotExist]
            private $inside;

            public function __construct()
            {
                $this->inside = 'inside';
                $this->runtime = new \Darken\Code\Runtime();
            }
        };
        PHP);

        $file = $this->createInputFile($tmpFile);

        $compiler = new CodeCompiler();
        $output = $compiler->compile($file);

        $code = <<<'PHP'
    <?php /** @var \Darken\Code\Runtime $this */ ?><?php

    use Darken\Attributes\Param;
    $x = new class($this)
    {
        public $runtime;
        #[DoesNotExist]
        private $inside;
        public function __construct(\Darken\Code\Runtime $runtime)
        {
            $this->inside = 'inside';
            $this->runtime = $runtime;
        }
    };
    PHP;

        $this->assertSame($code, $output->getCode());
    }

    public function testInvalidPhpAttributes()
    {
        $tmpFile = $this->createTmpFile('test.php', <<<'PHP'
        <?php
        $x = new class {
            #[DoesNotExist]
            public $inside;

            public function __construct()
            {
            }
        };
        PHP);

        $file = $this->createInputFile($tmpFile);

        $compiler = new CodeCompiler();
        $output = $compiler->compile($file);

        $code = <<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this */ ?><?php
        
        $x = new class($this)
        {
            protected \Darken\Code\Runtime $runtime;
            #[DoesNotExist]
            public $inside;
            public function __construct(\Darken\Code\Runtime $runtime)
            {
                $this->runtime = $runtime;
            }
        };
        PHP;

        $this->assertSame($code, $output->getCode());
    }

    public function testAlreadyDefinedRuntimeConstructor()
    {
        $tmpFile = $this->createTmpFile('test.php', <<<'PHP'
        <?php
        $x = new class {
            public function __construct($runtime, $another)
            {
            }
        };
        PHP);

        $file = $this->createInputFile($tmpFile);

        $compiler = new CodeCompiler();
        $output = $compiler->compile($file);

        $code = <<<'PHP'
<?php /** @var \Darken\Code\Runtime $this */ ?><?php

$x = new class($this)
{
    protected \Darken\Code\Runtime $runtime;
    public function __construct(\Darken\Code\Runtime $runtime, $another)
    {
        $this->runtime = $runtime;
    }
};
PHP;

        $this->assertSame($code, $output->getCode());
    }
}