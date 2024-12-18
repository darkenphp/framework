<?php

declare(strict_types=1);

namespace Darken\Enum;

enum MiddlewarePosition: string
{
    case BEFORE = 'before';
    case AFTER = 'after';
}
