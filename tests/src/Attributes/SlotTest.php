<?php

namespace Tests\src\Attributes;

use Darken\Attributes\Slot;
use Tests\TestCase;

class SlotTest extends TestCase
{
    public function testConstructor()
    {
        $slot = new Slot('test');
        $this->assertInstanceOf(Slot::class, $slot);
    }
}