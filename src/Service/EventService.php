<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Events\EventDispatchInterface;
use Darken\Events\EventInterface;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Event service for managing and dispatching events.
 *
 * This service provides PSR-14 compliant event dispatching and listener management.
 * It integrates with the ContainerService to support dependency injection for event listeners.
 */
final class EventService implements EventDispatcherInterface, ListenerProviderInterface
{
    /**
     * @var array<string, array<callable|string|array>> Registered event listeners indexed by event class name.
     */
    private array $listeners = [];

    /**
     * @param ContainerService $containerService The container service for resolving listener dependencies.
     */
    public function __construct(private ContainerService $containerService)
    {

    }

    /**
     * Gets all registered event listeners.
     *
     * @return array<string, array<callable|string|array>> An array of listeners indexed by event class name.
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * This method implements PSR-14 EventDispatcherInterface. It iterates through all listeners
     * registered for the event type and invokes them. Listeners can be callables, class names,
     * or arrays containing a class name and constructor parameters.
     *
     * @param object $event The event object to dispatch. Must implement EventDispatchInterface.
     * @return object The event object after all listeners have been invoked.
     * @throws InvalidArgumentException If the event does not implement EventDispatchInterface.
     */
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

    /**
     * Gets all listeners registered for a specific event type.
     *
     * This method implements PSR-14 ListenerProviderInterface.
     *
     * @param object $event The event object to get listeners for.
     * @return iterable<callable|string|array> An iterable of listeners for the event type.
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventType = get_class($event);
        return $this->listeners[$eventType] ?? [];
    }

    /**
     * Registers an event listener for a specific event type.
     *
     * This method supports multiple listener formats through the ContainerService's ensure() method:
     *
     * **1. Callable (closure or function):**
     * ```php
     * $eventService->on(MyEvent::class, function(MyEvent $event) {
     *     // Handle event
     * });
     * ```
     *
     * **2. Class name (string):**
     * - If the class is registered in the container, it will be resolved from there
     * - If not registered, it will be instantiated with automatic dependency injection
     * - The class must implement EventInterface and be invokable (__invoke method)
     * ```php
     * $eventService->on(MyEvent::class, MyEventListener::class);
     * ```
     *
     * **3. Array with class name and constructor parameters:**
     * - First element: class name (string)
     * - Second element (optional): array of constructor parameters
     * - Useful for creating listeners with specific configuration
     * ```php
     * $eventService->on(MyEvent::class, [MyEventListener::class, ['param1' => 'value']]);
     * ```
     *
     * **4. Object instance:**
     * - Directly pass an instantiated listener object
     * - Must implement EventInterface and be invokable
     * ```php
     * $eventService->on(MyEvent::class, new MyEventListener());
     * ```
     *
     * Note: All non-callable listeners must implement EventInterface and will be validated
     * during dispatch. The ContainerService will handle dependency resolution automatically.
     *
     * @param string $eventType The fully qualified class name of the event (e.g., MyEvent::class).
     * @param callable|string|array $listener The listener to register. Can be:
     *                                        - callable: A closure or function
     *                                        - string: A class name (resolved via ContainerService)
     *                                        - array: [className, constructorParams] for custom instantiation
     *                                        - object: An already instantiated listener object
     * @return self Returns the EventService instance for method chaining.
     */
    public function on(string $eventType, callable|string|array $listener): self
    {
        $this->listeners[$eventType][] = $listener;

        return $this;
    }
}
