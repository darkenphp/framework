<?php

declare(strict_types=1);

namespace Darken\Service;

interface MiddlewareServiceInterface
{
    public function middlewares(MiddlewareService $service): MiddlewareService;
}
