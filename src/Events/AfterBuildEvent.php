<?php

declare(strict_types=1);

namespace Darken\Events;

use Darken\Console\Application;

class AfterBuildEvent implements EventInterface
{
    public function __construct(public Application $app)
    {

    }
}
