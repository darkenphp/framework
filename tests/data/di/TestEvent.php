<?php

namespace Tests\data\di;

use Darken\Events\EventDispatchInterface;
use Darken\Events\EventInterface;

class TestEvent implements EventInterface
{
    public function __construct(public TestService $service)
    {
        
    }

    public function __invoke(EventDispatchInterface $event): void
    {
        if ($event instanceof TestEventDispatch) {
            $event->buffer = get_class($this->service);
        }
    }

    public static function handler(TestEventDispatch $event): void
    {
        $event->buffer = 'static';
    }
}