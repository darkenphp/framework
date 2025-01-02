<?php

declare(strict_types=1);

namespace Darken\Events;

interface EventInterface
{
    public function __invoke(EventDispatchInterface $event): void;
}
