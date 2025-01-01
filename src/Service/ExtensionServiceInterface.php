<?php

declare(strict_types=1);

namespace Darken\Service;

interface ExtensionServiceInterface
{
    public function extensions(ExtensionService $service): ExtensionService;
}
