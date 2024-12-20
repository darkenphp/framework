<?php

declare(strict_types=1);

namespace Darken\Service;

interface PluginServiceInterface
{
    public function plugins(PluginService $service): PluginService;
}
