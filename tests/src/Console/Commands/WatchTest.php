<?php

namespace Tests\src\Console\Commands;

use Darken\Console\Application;
use Tests\TestCase;

class WatchTest extends TestCase
{
    public function testWatchCommand()
    {
        $cfg = $this->createConfig();
        $app = new Application($cfg);
        $this->assertInstanceOf(Application::class, $app);

        $this->clear();
    }
}