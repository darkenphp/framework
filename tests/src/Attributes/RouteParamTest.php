<?php

namespace Tests\src\Attributes;

use Darken\Attributes\RouteParam;
use Tests\TestCase;

class RouteParamTest extends TestCase
{
    public function testConstructor()
    {
        $routeParam = new RouteParam('test');
        $this->assertInstanceOf(RouteParam::class, $routeParam);
    }
}