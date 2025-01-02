<?php

namespace Tests\data\di;

use Darken\Events\EventDispatchInterface;

class TestEventDispatch implements EventDispatchInterface
{
    public string $buffer = '';
}