<?php

namespace Tests\src\Builder;

use Darken\Builder\CodeCompiler;
use Darken\Builder\OutputCompiled;
use Darken\Builder\OutputPolyfill;
use Tests\TestCase;

class CodeCompilerAttributesTest extends TestCase
{
    public function testArrayAndOtherTypes()
    {
        $tmpFile = $this->createTmpFile('test.php', <<<'PHP'
        <?php
        use \Darken\Attributes\Param;
        $x = new class {

            #[Param]
            public string $stringvar = 'test';

            #[Param]
            public int $zahl;

            #[Param]
            public array $array;

            #[Param]
            public array $array2 = [];
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
    protected \Darken\Code\Runtime $runtime;
    #[Param]
    public string $stringvar = 'test';
    #[Param]
    public int $zahl;
    #[Param]
    public array $array;
    #[Param]
    public array $array2 = [];
    public function __construct(\Darken\Code\Runtime $runtime)
    {
        $this->runtime = $runtime;
        $this->stringvar = $this->runtime->getArgumentParam('stringvar');
        $this->zahl = $this->runtime->getArgumentParam('zahl');
        $this->array = $this->runtime->getArgumentParam('array');
        $this->array2 = $this->runtime->getArgumentParam('array2');
    }
};
PHP;

        $this->assertSame($code, $output->getCode());

        $outputCompiled = new OutputCompiled($output->getCode(), $file, $this->createConfig());

        $polyfill = new OutputPolyfill($outputCompiled, $output);

        $this->assertSame(
            <<<'PHP'
<?php
namespace Tests\Build\tmp;

class test extends \Darken\Code\Runtime
{
    public function __construct(int $zahl, array $array, string $stringvar = 'test', array $array2 = [])
    {
        $this->setArgumentParam("stringvar", $stringvar);
        $this->setArgumentParam("zahl", $zahl);
        $this->setArgumentParam("array", $array);
        $this->setArgumentParam("array2", $array2);
    }

    
    public function renderFilePath(): string
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR  . 'test.compiled.php';
    }
}
PHP, $polyfill->getBuildOutputContent());
    }
}