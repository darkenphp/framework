<?php

namespace Tests\src\Service;

use App\Test;
use Darken\Kernel;
use Darken\Service\ExtensionInterface;
use Darken\Service\ExtensionService;
use Darken\Service\ExtensionServiceInterface;
use Darken\Web\Application;
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
                        $kernel->getEventService()->on(TestService::class, function () {
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
        $app->getEventService()->dispatch(new TestService());
        $output = ob_get_clean();

        $this->assertEquals('TestService event triggered', $output);
        
    }
}