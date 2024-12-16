<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Param
{
    public function __construct(public ?string $name = null)
    {
        // if defined, the name will be taken to resolve the param
        // which means you can use another argument name for the actuall
        // parameter name
    }
}
