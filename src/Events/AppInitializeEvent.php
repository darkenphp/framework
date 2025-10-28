<?php

declare(strict_types=1);

namespace Darken\Events;

use Darken\Kernel;

class AppInitializeEvent implements EventDispatchInterface
{
    public function __construct(public Kernel $app)
    {

    }
}
