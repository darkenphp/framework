<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;
use Darken\Enum\MiddlewarePosition;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Middleware
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(public string $class, public array $params = [], public MiddlewarePosition $position = MiddlewarePosition::BEFORE)
    {
    }
}
