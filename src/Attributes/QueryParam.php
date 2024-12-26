<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;

/**
 * An attribute that marks a property to be populated from the query string (e.g., `?foo=bar`).
 *
 * If a custom `$name` is provided, that query parameter name will be used. Otherwise, the
 * property’s name is used by default.
 *
 * Example usage:
 *
 * ```php
 * #[QueryParam('search')]
 * public string $searchQuery;
 *
 * // Here, if the URL is: /products?search=keyword
 * // The property $searchQuery will be populated with "keyword".
 *
 * #[QueryParam]
 * public int $page;
 *
 * // If the URL is: /products?page=2
 * // The property $page is automatically populated with 2 (assuming type casting).
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class QueryParam
{
    /**
     * @param string|null $name The name of the query parameter. If null, the property’s own name is used.
     */
    public function __construct(public ?string $name = null)
    {
    }
}
