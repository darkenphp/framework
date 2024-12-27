<?php

declare(strict_types=1);

namespace Darken\Enum;

enum HookedAttributeType: string
{
    case ON_PROPERTY = 'property';
    case ON_METHOD = 'method';
    case ON_CLASS = 'class';
}
