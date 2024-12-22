<?php

namespace Tests\src\Console;

use Darken\Console\Application;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    public function testArgvs()
    {
        $_SERVER['argv'] = ['darken', 'build', '--foo=1', '--bar=test'];

        $app = new Application($this->createConfig());

        $this->assertSame('darken', $app->getBin());
        $this->assertSame('build', $app->getCommand());
        $this->assertSame(['foo' => 1, 'bar' => 'test'], $app->getArguments());
        $this->assertSame(1, $app->getArgument('foo', 'default'));
        $this->assertSame('default', $app->getArgument('doesnotexists', 'default'));
    }
}