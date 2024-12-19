<?php declare(strict_types=1);

namespace Tests;

error_reporting(E_ALL);
ini_set('display_errors', '1');

use Darken\Config\ConfigInterface;
use PHPUnit\Framework\TestCase as FrameworkTestCase;

class TestCase extends FrameworkTestCase
{
    public function createConfig() : ConfigInterface
    {
        return new TestConfig(__DIR__);
    }
}