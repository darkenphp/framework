<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;

/**
 * An attribute that marks a property to be populated from POST data (e.g., form submissions).
 *
 * If a custom `$name` is provided, that POST field name will be used. Otherwise, the
 * property’s name is used by default.
 *
 * Example usage:
 *
 * ```php
 * #[PostParam('username')]
 * public string $user;
 *
 * // In a POST request with `username=JohnDoe`, the $user property
 * // will be populated with "JohnDoe".
 *
 * #[PostParam]
 * public string $password;
 *
 * // In a POST request with `password=mySecret`, the $password
 * // property will be populated with "mySecret".
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class PostParam
{
    /**
     * @param string|null $name The name of the POST field. If null, the property’s own name is used.
     */
    public function __construct(public ?string $name = null)
    {
    }
}
