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
        $cfg = $this->createConfig();
        $tmpFile = $this->createTmpFile($cfg, 'test.php', <<<'PHP'
        <?php
        use \Darken\Attributes\ConstructorParam;
        $x = new class {

            #[ConstructorParam]
            public string $stringvar = 'test';

            #[ConstructorParam]
            public int $zahl;

            #[ConstructorParam]
            public array $array;

            #[ConstructorParam]
            public array $array2 = [];
        };
        PHP);

        $file = $this->createInputFile($tmpFile);

        $compiler = new CodeCompiler();
        $output = $compiler->compile($file);

        $code = <<<'PHP'
<?php /** @var \Darken\Code\Runtime $this */ ?><?php

use Darken\Attributes\ConstructorParam;
$x = new class($this)
{
    protected \Darken\Code\Runtime $runtime;
    #[ConstructorParam]
    public string $stringvar = 'test';
    #[ConstructorParam]
    public int $zahl;
    #[ConstructorParam]
    public array $array;
    #[ConstructorParam]
    public array $array2 = [];
    public function __construct(\Darken\Code\Runtime $runtime)
    {
        $this->runtime = $runtime;
        $this->array2 = $this->runtime->getArgumentParam('array2');
        $this->array = $this->runtime->getArgumentParam('array');
        $this->zahl = $this->runtime->getArgumentParam('zahl');
        $this->stringvar = $this->runtime->getArgumentParam('stringvar');
    }
};
PHP;

        $this->assertSame($code, $output->getCode());

        $outputCompiled = new OutputCompiled($output->getCode(), $file, $this->createConfig());

        $polyfill = new OutputPolyfill($outputCompiled, $output);

        $this->assertSame(
            <<<'PHP'
<?php

namespace Tests\Build\data\generated;

class test extends \Darken\Code\Runtime
{
    public function __construct(int $zahl, array $array, string $stringvar = 'test', array $array2 = [])
    {
        $this->setArgumentParam('zahl', $zahl);
        $this->setArgumentParam('array', $array);
        $this->setArgumentParam('stringvar', $stringvar);
        $this->setArgumentParam('array2', $array2);
    }
    public function renderFilePath(): string
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'test.compiled.php';
    }
}
PHP, $polyfill->getBuildOutputContent());
    }

    public function testInjectionBeforeOtherProps()
    {
        $tmpFile = $this->createTmpFile($this->createConfig(), 'test.php', <<<'PHP'
        <?php

        use Darken\Attributes\Inject;
        use Darken\Attributes\RouteParam;
        use Flyo\Api\ConfigApi;
        use Tests\data\di\Db;

        $cms = new class {
            #[Inject]
            public Db $config;

            #[RouteParam]
            public string $slug;

            public function __construct()
            {
                $cfg = new ConfigApi(null, $this->config);
                $data = $cfg->getConfig();
                
            }
        };

        ?>
        PHP);

        $file = $this->createInputFile($tmpFile);

        $compiler = new CodeCompiler();
        $output = $compiler->compile($file);

        $this->assertSame(<<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this */ ?><?php

        use Darken\Attributes\Inject;
        use Darken\Attributes\RouteParam;
        use Flyo\Api\ConfigApi;
        use Tests\data\di\Db;
        $cms = new class($this)
        {
            protected \Darken\Code\Runtime $runtime;
            #[Inject]
            public Db $config;
            #[RouteParam]
            public string $slug;
            public function __construct(\Darken\Code\Runtime $runtime)
            {
                $this->runtime = $runtime;
                $this->slug = $this->runtime->getRouteParam('slug');
                $this->config = $this->runtime->getContainer(\Tests\data\di\Db::class);
                $cfg = new ConfigApi(null, $this->config);
                $data = $cfg->getConfig();
            }
        };
        PHP, $output->getCode());
    }
}