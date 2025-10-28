<?php

namespace Tests\src\Console\Commands;

use Darken\Console\Application;
use Darken\Console\Commands\Dev;
use Tests\TestCase;

class DevTest extends TestCase
{
    public function testDevCommand()
    {
        $cfg = $this->createConfig();
        $app = new Application($cfg);
        
        ob_start();
        $cmd = new Dev();
        $cmd->terminateProcesses();
        $output = ob_get_clean();

        $this->assertEmpty($output);

        $this->clear();
    }
}