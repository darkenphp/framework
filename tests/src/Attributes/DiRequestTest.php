<?php

namespace Tests\src\Attributes;

use Tests\TestCase;

class DiRequestTest extends TestCase
{

    public function testRequestDi()
    {
        $className = $this->createCompileTest($this->createConfig(), <<<'PHP'
        <?php
        $o = new class {
            #[\Darken\Attributes\Inject]
            public \Darken\Web\Request $request;

            public function getParamX()
            {
                return $this->request->getQueryParams()['x'];
            }
        };
        ?>
        param: <?= $o->getParamX(); ?>
        PHP,
        <<<'PHP'
        <?php /** @var \Darken\Code\Runtime $this */ ?><?php

        $o = new class($this)
        {
            protected \Darken\Code\Runtime $runtime;
            #[\Darken\Attributes\Inject]
            public \Darken\Web\Request $request;
            public function getParamX()
            {
                return $this->request->getQueryParams()['x'];
            }
            public function __construct(\Darken\Code\Runtime $runtime)
            {
                $this->runtime = $runtime;
                $this->request = $this->runtime->getContainer(\Darken\Web\Request::class);
            }
        };
        ?>
        param: <?php 
        echo $o->getParamX();
        PHP,
        <<<'PHP'
        <?php

        namespace Tests\Build\data\generated;

        class test extends \Darken\Code\Runtime
        {
            function __construct()
            {
            }
            public function renderFilePath(): string
            {
                return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'test.compiled.php';
            }
        }
        PHP);

        $this->mockWebAppRequest($this->createConfig(), 'GET', '?x=test');
        $runtime = $this->createRuntime($className);

        $this->assertSame('param: test', $runtime->render());
    }
}