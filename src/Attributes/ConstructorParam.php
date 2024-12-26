<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;

/**
 * An attribute that maps a constructor parameter to the annotated property in a class.
 * 
 * If a custom `$name` is provided, that name will be used to match against the parameter
 * in the constructor. If `$name` is omitted or `null`, the property’s name is used by default.
 * 
 * This can be helpful when the property name in your class does not match the parameter 
 * name in the constructor, or when you want to make your property names more descriptive.
 * 
 * Example usage:
 *
 * ```php
 * class Example
 * {
 *     public function __construct(
 *         public string $bar,
 *         public int $baz
 *     ) {}
 *
 *     #[ConstructorParam('bar')]
 *     public string $someBar;
 *
 *     #[ConstructorParam('baz')]
 *     public int $someBaz;
 * }
 *
 * // Here, $someBar will be mapped from the constructor parameter 'bar',
 * // and $someBaz will be mapped from the constructor parameter 'baz'.
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ConstructorParam
{
    /**
     * @param string|null $name The name of the constructor parameter to map to this property. 
     *                          If null, the property name itself is used.
     */
    public function __construct(public ?string $name = null)
    {
        // If $name is specified, that exact parameter name will be resolved.
        // Otherwise, the name of the property will be used as the parameter name.
    }
}
