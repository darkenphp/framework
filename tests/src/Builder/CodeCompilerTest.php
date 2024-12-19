<?php

namespace Tests\src\Builder;

use PhpParser\Node\Scalar\String_;
use Darken\Builder\CodeCompiler;
use Darken\Builder\OutputCompiled;
use Darken\Builder\OutputPolyfill;
use Tests\TestCase;

class CodeCompilerTest extends TestCase
{
    public function testLayoutFile()
    {
        $inputFile = $this->createInputFile(__DIR__ . '/../../data/components/layout1.php');

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

        // Define the expected 'constructor' meta data
        $expectedConstructor = [
            [
                'attrName'      => 'Darken\Attributes\Param',
                'propertyName'  => 'arg1',
                'paramName'     => 'arg1',
                'paramType'     => 'string',
                'arg'           => new String_('arg1'),
            ],
            [
                'attrName'      => 'Darken\Attributes\Param',
                'propertyName'  => 'namedArg2',
                'paramName'     => 'nmdArgu2',
                'paramType'     => 'string',
                'arg'           => new String_('nmdArgu2'),
            ],
        ];

        // Assert that 'constructor' matches the expected structure
        $this->assertEquals($expectedConstructor, $output->getMeta('constructor'), "Constructor meta data does not match.");

        // Define the expected 'slots' meta data
        $expectedSlots = [
            [
                'attrName'      => 'Darken\Attributes\Slot',
                'propertyName'  => 'slot1',
                'paramName'     => 'slot1',
                'paramType'     => 'string',
                'arg'           => new String_('slot1'),
            ],
            [
                'attrName'      => 'Darken\Attributes\Slot',
                'propertyName'  => 'slot2',
                'paramName'     => 'nmdSlot2',
                'paramType'     => 'string',
                'arg'           => new String_('nmdSlot2'),
            ],
        ];

        // Assert that 'slots' matches the expected structure
        $this->assertEquals($expectedSlots, $output->getMeta('slots'), "Slots meta data does not match.");

        $outputCompiled = new OutputCompiled($output->getCode(), $inputFile, $this->createConfig());
        $this->assertStringContainsString('tests/src/Builder/../../data/components/layout1.compiled.php', $outputCompiled->getBuildOutputFilePath());
        $this->assertSame('/tests/src/Builder/../../data/components', $outputCompiled->getRelativeDirectory());
        $this->assertSame('/tests/src/Builder/../../data/components/layout1.php', $outputCompiled->getFilePath());

        $polyfill = new OutputPolyfill($outputCompiled, $output);
        $this->assertSame('Build\tests\src\Builder\..\..\data\components', $polyfill->getNamespace());
        $this->assertStringContainsString('tests/src/Builder/../../data/components/layout1.ph', $polyfill->getBuildOutputFilePath());
        $this->assertSame('layout1.compiled.php', $polyfill->getRelativeBuildOutputFilePath());
        $this->assertSame('layout1', $polyfill->getClassName());

        $this->assertSame(
<<<'PHP'
<?php
namespace Build\tests\src\Builder\..\..\data\components;

class layout1 extends \Darken\Code\Runtime
{
    public function __construct(string $arg1, string $nmdArgu2)
    {
        $this->setArgumentParam("arg1", $arg1);
        $this->setArgumentParam("namedArg2", $nmdArgu2);
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
        return dirname(__FILE__) . DIRECTORY_SEPARATOR  . 'layout1.compiled.php';
    }
}
PHP, $polyfill->getBuildOutputContent()
        );

    }
}