<?php

namespace Tests\data\di;

class AutoWireTestService
{
    public function __construct(private TestService $service)
    {
        
    }

    public function testServiceName() : string
    {
        return get_class($this->service);
    }
}