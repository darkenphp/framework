<?php

declare(strict_types=1);

namespace Darken\Service;

interface ContainerSericeInterface
{
    public function containers(ContainerService $service): ContainerService;
}
