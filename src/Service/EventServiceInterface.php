<?php

declare(strict_types=1);

namespace Darken\Service;

interface EventServiceInterface
{
    public function events(EventService $service): EventService;
}
