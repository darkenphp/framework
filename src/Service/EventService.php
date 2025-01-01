<?php

declare(strict_types=1);

namespace Darken\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventService implements EventDispatcherInterface, ListenerProviderInterface
{
    private array $listeners = [];

    /**
     * @return array<string, array<callable>>
     */
    public function getListeneres(): array
    {
        return $this->listeners;
    }

    public function dispatch(object $event): object
    {
        foreach ($this->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        return $event;
    }

    public function getListenersForEvent(object $event): iterable
    {
        $eventType = get_class($event);
        return $this->listeners[$eventType] ?? [];
    }

    public function on(string $eventType, callable $listener): self
    {
        $this->listeners[$eventType][] = $listener;

        return $this;
    }
}
