<?php

namespace Tests\data\di;

class Db
{
    public function __construct(public string $dsn)
    {
        
    }

    public function getUpperDsn() : string
    {
        return strtoupper($this->dsn);
    }
}