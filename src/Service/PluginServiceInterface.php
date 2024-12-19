<?php

namespace Darken\Service;

interface PluginServiceInterface
{
    public function plugins(PluginService $service): PluginService;
}