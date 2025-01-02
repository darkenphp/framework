<?php

declare(strict_types=1);

namespace Darken\Events;

use Darken\Console\Application;

class AfterBuildEvent implements EventDispatchInterface
{
    public function __construct(public Application $app)
    {

    }
}
