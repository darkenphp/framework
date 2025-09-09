<?php

namespace Tests\src\Web;

use Darken\Config\ConfigInterface;
use Darken\Enum\MiddlewarePosition;
use Darken\Web\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\TestCase;
use Tests\TestConfig;

class ApplicationTest extends TestCase
{
    public function testApplicationGlobals()
    {
        $cfg = $this->createConfig();
        $cfg->setDebugMode(true);

        $app = new Application($cfg);

        $mid = new class implements MiddlewareInterface
        {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);
                $response->withHeader('X-Test', 'test');
                return $response;
            }
        };

        $app->getMiddlewareService()->register($mid, MiddlewarePosition::BEFORE);
        $app->whoops->unregister();

        ob_start();
        $app->run();
        $output = ob_get_clean();

        $this->assertSame('Page not found', $output);

        $this->assertInstanceOf(TestConfig::class, $app->getContainerService()->resolve(TestConfig::class));
        $this->assertInstanceOf(ConfigInterface::class, $app->getContainerService()->resolve(ConfigInterface::class));
        
    }
    /*
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

    public function testExceptionPage()
    {
        $app = new Application($this->createConfig());
        $app->whoops->unregister();

        $this->destoryTmpFile($app->config->getBuildOutputFolder() . DIRECTORY_SEPARATOR . 'routes.php');

        ob_start();
        $app->run();
        $output = ob_get_clean();

        $this->assertSame('Page not found', $output);
        $this->clear();
    }
}