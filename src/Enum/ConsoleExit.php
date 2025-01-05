<?php

declare(strict_types=1);

namespace Darken\Enum;

enum ConsoleExit: int
{
    case SUCCESS = 0;
    case ERROR = 1;
    case MISSING_ARGUMENT = 2;
    case INVALID_INPUT = 3;
}
