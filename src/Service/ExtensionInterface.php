<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Kernel;

interface ExtensionInterface
{
    public function activate(Kernel $kernel): void;
}
