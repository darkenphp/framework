<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Kernel;

use function Opis\Closure\unserialize;

abstract class Extension implements ExtensionInterface
{
    private $definitions = [];

    abstract public function getClassMap(): array;

    abstract public function getSerializedEvents(): string;

    abstract public function getSerializedMiddlewares(): string;

    public function registerDefinition(string $containerName, object $object): void
    {
        $this->definitions[$containerName] = $object;
    }

    public function activate(Kernel $kernel): void
    {
        foreach ($this->definitions as $containerName => $object) {
            $kernel->getContainerService()->register($containerName, $object);
        }

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
            $kernel->getMiddlwareService()->register($middleware['container'], $middleware['position']);
        }
    }
}
