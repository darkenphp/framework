<?php

namespace App;

use Darken\Service\LogService;

class Test
{
    public function __construct(LogService $log)
    {
        $log->alert('hey');
    }
}