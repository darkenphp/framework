<?php

namespace Tests\src\Attributes;

use Darken\Attributes\Inject;
use Tests\TestCase;

class InjectTest extends TestCase
{
    public function testConstructor()
    {
        $inject = new Inject('test');
        $this->assertInstanceOf(Inject::class, $inject);
    }
}