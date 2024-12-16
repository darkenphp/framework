<?php

declare(strict_types=1);

namespace Darken;

use Darken\Repository\Config;

error_reporting(E_ALL);
ini_set('display_errors', '1');

class Kernel
{
    public function __construct(public readonly Config $config)
    {
    }
}
