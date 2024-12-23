<?php

namespace Tests\src\Config;

use App\Test;
use Darken\Config\ConfigHelperTrait;
use Tests\TestCase;

class ConfigHelperTraitTest extends TestCase
{

    public function testGetConfig()
    {
        // loads the .env file
        $config = $this->createConfig();

        $this->assertSame('test', getenv('ENV'));
        $this->assertSame("1", getenv('DEBUG'));
        $this->assertSame("true", getenv('OFFLINE'));
        $this->assertSame("True", getenv('Online'));
    }
}