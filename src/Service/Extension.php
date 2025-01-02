<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Kernel;

use function Opis\Closure\unserialize;

abstract class Extension implements ExtensionInterface
{
    abstract public function getClassMap(): array;

    abstract public function getSerializedEvents(): string;

    abstract public function getSerializedMiddlewares(): string;

    public function activate(Kernel $kernel): void
    {
        $events = base64_decode($this->getSerializedEvents());
        $eventHandlers = unserialize($events);

        foreach ($eventHandlers as $event => $handlers) {
            foreach ($handlers as $handler) {
                $kernel->getEventService()->on($event, $handler);
            }
        }

        $middlewares = base64_decode($this->getSerializedMiddlewares());
        $middlewareHandlers = unserialize($middlewares);

        foreach ($middlewareHandlers as $middleware) {
            $kernel->getMiddlwareService()->add($middleware['object'], $middleware['position']);
        }
    }
}
