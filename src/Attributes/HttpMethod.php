<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;

/**
 * Either a comma separated string or an array of HTTP verbs that this middleware should be applied to.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class HttpMethod
{
    public function __construct(
        public string|array $verbs
    ) {
    }
}
