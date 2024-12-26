<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;

/**
 * An attribute that marks a property as a “slot,” which can be used to inject or map data
 * from an external source (e.g., templates, components, or any slot-based mechanism).
 *
 * If a custom `$name` is provided, that will be used as the slot identifier.
 * If `$name` is omitted or `null`, the property’s own name is used as the slot identifier.
 *
 * Example usage:
 *
 * ```php
 * #[Slot('header')]
 * public string $headerContent;
 *
 * // This property will be linked to the slot named 'header'.
 * // If 'headerContent' is not your desired slot name, you can explicitly
 * // set it by providing 'header' here.
 *
 * #[Slot]
 * public string $footer;
 *
 * // If $name is omitted, 'footer' is used as the slot identifier by default.
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Slot
{
    /**
     * @param string|null $name The identifier of the slot. If null, the property's own name is used.
     */
    public function __construct(public ?string $name = null)
    {
        // If $name is specified, it will be used as the slot identifier.
        // Otherwise, the property name will be used.
    }
}
