<?php

namespace Tests\src\Web;

use Darken\Web\Application;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    public function testApplicationGlobals()
    {
        $app = new Application($this->createConfig());
        $app->whoops->unregister();

        ob_start();
        $app->run();
        $output = ob_get_clean();

        $this->assertSame('pages/[[...slug]]:', $output);
    }

    public function test404Application()
    {
        $cfg = $this->createConfig();
        $cfg->setPagesFolder('does/not/exist');
        $cfg->setBuilderOutputFolder('does/not/exist');

        $app = new Application($cfg);
        $app->whoops->unregister();

        $_SERVER['REQUEST_URI'] = '/404';
        ob_start();
        $app->run();
        $output = ob_get_clean();

        $this->assertSame('Page not found', $output);
    }
}