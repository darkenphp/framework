<?php

namespace Tests\src\Web;

use Darken\Web\Application;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    /*
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
        */

    public function testRequestDi()
    {
        $request = $this->createServerRequest('/test.php?x=test', 'GET');

        //
        // Debugging: Dump query parameters

        // Continue with assertions
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/test.php?x=test', $request->getRequestTarget());
        $this->assertEquals(['x' => 'test'], $request->getQueryParams());
    }

}