<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class HttpVerb
{
    public function __construct(
        public array|string $verb,
    ) {
        // ...
    }
}
