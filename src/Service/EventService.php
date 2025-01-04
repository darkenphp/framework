<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Events\EventDispatchInterface;
use Darken\Events\EventInterface;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

final class EventService implements EventDispatcherInterface, ListenerProviderInterface
{
    private array $listeners = [];

    public function __construct(private ContainerService $containerService)
    {

    }

    /**
     * @return array<string, array<callable>>
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }

    public function dispatch(object $event): object
    {
        if (!$event instanceof EventDispatchInterface) {
            throw new InvalidArgumentException('Event must implement EventDispatchInterface');
        }

        foreach ($this->getListenersForEvent($event) as $listener) {
            if (is_callable($listener)) {
                $function = $listener;
            } else {
                $function = $this->containerService->ensure($listener, EventInterface::class);
            }
            $function($event);
        }

        return $event;
    }

    public function getListenersForEvent(object $event): iterable
    {
        $eventType = get_class($event);
        return $this->listeners[$eventType] ?? [];
    }

    public function on(string $eventType, callable|string|array $listener): self
    {
        $this->listeners[$eventType][] = $listener;

        return $this;
    }
}
