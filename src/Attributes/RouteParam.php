<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class RouteParam
{
    public function __construct(public string $name)
    {
    }
}
