<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Kernel;

class ExtensionService
{
    public function __construct(private Kernel $kernel)
    {

    }

    public function register(ExtensionInterface $extension): self
    {
        $extension->activate($this->kernel);

        return $this;
    }
}
