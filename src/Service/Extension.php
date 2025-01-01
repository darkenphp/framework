<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Kernel;

use function Opis\Closure\unserialize;

abstract class Extension implements ExtensionInterface
{
    abstract public function getClassMap(): array;

    abstract public function getSerializedEvents(): string;

    public function activate(Kernel $kernel): void
    {
        $x = base64_decode($this->getSerializedEvents());
        $eventHandlers = unserialize($x);

        foreach ($eventHandlers as $event => $handlers) {
            foreach ($handlers as $handler) {
                $kernel->getEventService()->on($event, $handler);
            }
        }
    }
}
