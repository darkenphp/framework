<?php

declare(strict_types=1);

namespace Darken\Service;

interface MiddlewareServiceInterface
{
    public function middlwares(MiddlewareService $service): MiddlewareService;
}
