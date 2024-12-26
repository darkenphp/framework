<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;

/**
 * An attribute that maps a route parameter (e.g., `{id}` in a route like `/users/{id}`)
 * to the annotated property in a class.
 *
 * If a custom `$name` is provided, that route parameter name will be used. Otherwise,
 * the property’s own name will be used by default. This can be especially helpful when
 * the route parameter name does not match the property name you wish to use in your code.
 *
 * Example usage:
 *
 * ```php
 * // Given a route: /users/{id}
 *
 * #[RouteParam('id')]
 * public string $userId;
 *
 * // The route param "id" is thus mapped to $userId,
 * // which can be more descriptive within your codebase.
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class RouteParam
{
    /**
     * @param string|null $name The route parameter name to map to the property. If null, the property’s own name will be used.
     */
    public function __construct(public ?string $name = null)
    {
        // If $name is defined, it will be used to resolve the route parameter.
        // Otherwise, the property name itself will be used.
    }
}
