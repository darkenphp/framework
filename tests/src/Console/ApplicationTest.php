<?php

namespace Tests\src\Console;

use Darken\Console\Application;
use Darken\Console\CommandInterface;
use Darken\Enum\ConsoleExit;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    public function testArgvs()
    {
        $_SERVER['argv'] = ['darken', 'build', '--foo=1', '--bar=test'];

        $app = new Application($this->createConfig());

        $this->assertSame('darken', $app->getBin());
        $this->assertSame('build', $app->getCommandName());
        $this->assertSame(['foo' => 1, 'bar' => 'test'], $app->getArguments());
        $this->assertSame(1, $app->getArgument('foo', 'default'));
        $this->assertSame('default', $app->getArgument('doesnotexists', 'default'));

        $text = $app->stdTextGreen('test');
        $this->assertSame("\033[32mtest\033[0m", $text);

        $text = $app->stdTextRed('test');
        $this->assertSame("\033[31mtest\033[0m", $text);

        $text = $app->stdTextYellow('test');
        $this->assertSame("\033[33mtest\033[0m", $text);

        // std out for testing
        $app->stdOut(
            $app->stdTextGreen('da') . 
            $app->stdTextRed('rk') . 
            $app->stdTextYellow('en')
        );

        $this->clear();
    }

    public function testEmptyButDefinedArgv()
    {
        $_SERVER['argv'] = ['darken', 'build', '--clear', '--clear2=0', '--clear3=false', '--clear4=true', '--clear5=1'];

        $app = new Application($this->createConfig());

        $this->assertSame('darken', $app->getBin());
        $this->assertSame('build', $app->getCommandName());
        $this->assertSame([
            'clear' => true, 
            'clear2' => 0,
            'clear3' => false,
            'clear4' => true,
            'clear5' => 1
        ], $app->getArguments());

        $this->assertTrue($app->getArgument('clear', true));

        $this->assertFalse($app->getArgument('nodefined', false));

        $this->clear();
    }

    public function testRunBuild()
    {
        $config = $this->createConfig();
        $config->setDebugMode(true);
        $app = new Application($config);

        $_SERVER['argv'] = ['darken', 'build', '--clear'];
        ob_start();
        $app->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Compiled', $output);

        $this->clear();
    }

    public function testRegisterCommand()
    {
        $app = new Application($this->createConfig());

        $cmd = new class implements CommandInterface
        {
            public function run(Application $app): ConsoleExit
            {
                $app->stdOut('Hello World');
                return ConsoleExit::SUCCESS;
            }
        };

        $app->registerCommand('hello', $cmd);
        $_SERVER['argv'] = ['darken', 'hello'];

        ob_start();
        $app->run();
        $output = ob_get_clean();

        $this->assertSame('Hello World', trim($output));

        $this->clear();
    }

    public function testUnknownCommand()
    {
        $app = new Application($this->createConfig());

        $_SERVER['argv'] = ['darken', 'unknown'];

        ob_start();
        $app->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Command "unknown" not found.', $output);

        $this->clear();
    }

    public function testCommandWhichThrowsAnException()
    {
        $app = new Application($this->createConfig());

        $cmd = new class implements CommandInterface
        {
            public function run(Application $app): ConsoleExit
            {
                throw new \Exception('Test Exception');
            }
        };

        $app->registerCommand('exception', $cmd);
        $_SERVER['argv'] = ['darken', 'exception'];

        ob_start();
        $app->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Test Exception', $output);

        $this->clear();
    }
}