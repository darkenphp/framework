<?php

namespace Tests\src\Service;

use App\Test;
use Darken\Builder\ExtensionFile;
use Darken\Console\Application as ConsoleApplication;
use Darken\Console\Commands\FileBuildProcess;
use Darken\Enum\MiddlewarePosition;
use Darken\Kernel;
use Darken\Service\ExtensionInterface;
use Darken\Service\ExtensionService;
use Darken\Service\ExtensionServiceInterface;
use Darken\Service\MiddlewareService;
use Darken\Service\MiddlewareServiceInterface;
use Darken\Web\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\data\di\TestEvent;
use Tests\data\di\TestEventDispatch;
use Tests\data\di\TestService;
use Tests\TestCase;
use Tests\TestConfig;

class ExtensionTest extends TestCase
{
    public function testRegisterExtension(): void
    {
        $cfg = new class('root', 'root', 'root', 'root') extends TestConfig implements ExtensionServiceInterface
        {
            public function extensions(ExtensionService $service): ExtensionService
            {
                $ext = new class implements ExtensionInterface {
                    public function activate(Kernel $kernel): void
                    {
                        $kernel->getEventService()->on(TestEventDispatch::class, function () {
                            echo "TestService event triggered";
                        });
                    }
                };

                return $service->register(new $ext());
            }
        };

        $app = new Application($cfg);

        restore_error_handler();
        restore_exception_handler();

        ob_start();
        $app->getEventService()->dispatch(new TestEventDispatch());
        $output = ob_get_clean();

        $this->assertEquals('TestService event triggered', $output);
        
    }

    public function testMiddlwareOfExtension(): void
    {
       
        $extConfig = new class (
            rootDirectoryPath: $this->getTestsRootFolder(),
            pagesFolder: 'data/pages',
            componentsFolder: 'data/components',
            builderOutputFolder: '.build'
        ) extends TestConfig implements MiddlewareServiceInterface
        {
            public function middlewares(MiddlewareService $service): MiddlewareService
            {
                $testMiddlware = new class implements MiddlewareInterface
                {
                    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                    {
                        // Proceed to the next middleware or handler and get the response
                        $response = $handler->handle($request);

                        $body = $response->getBody();
                        $body->write('Hello World');

                        // Add the custom header to the response
                        return $response->withBody($body);
                    }
                };

                return $service->register($testMiddlware, MiddlewarePosition::AFTER);
            }
        };

        $cli = new ConsoleApplication($extConfig);
        $cli->whoops->unregister();

        $ext = new ExtensionFile([], $cli);
        $ext->className = 'ExtensionTest';

        $tmpFile = $this->tmpFile($ext->getBuildOutputFilePath(), $ext->getBuildOutputContent());

        $this->assertTrue(file_exists($tmpFile));

        // the tmp file with the class will be builded, and deleted after the test
        // therefore the IDE might say the file does not exists, but it does
        $extension = new \Tests\Build\ExtensionTest;

        $freshApp = new Application($this->createConfig());
        $freshApp->whoops->unregister();
        $freshApp->getExtensionService()->register($extension);

        $firstMiddlwareFromAbove = $freshApp->getMiddlwareService()->retrieve()[0];

        $this->assertInstanceOf(MiddlewareInterface::class, $firstMiddlwareFromAbove['container']);

        $this->destoryTmpFile($tmpFile);
    }
}