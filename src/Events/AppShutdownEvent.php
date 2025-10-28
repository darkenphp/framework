<?php

declare(strict_types=1);

namespace Darken\Events;

use Darken\Kernel;

class AppShutdownEvent implements EventDispatchInterface
{
    public function __construct(public Kernel $app)
    {

    }
}
