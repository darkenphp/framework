<?php

namespace App;

class Test
{
    public function __construct(private string $test)
    {
        
    }

    public function getUpperCase(): string
    {
        return strtoupper($this->test);
    }
}
