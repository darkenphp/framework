<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;

/**
 * An attribute that maps a constructor parameter to the annotated property in a class.
 *
 * IMPORTANT: Constructor parameter order matters for compiled class compatibility.
 * When refactoring code, simply rearranging properties can break compatibility.
 * Use the optional `$order` parameter to ensure consistent constructor signatures.
 *
 * ## Why Order Matters
 *
 * Without explicit ordering, parameter order depends on property declaration order:
 *
 * ```php
 * // Version 1: Properties in one order
 * class Example {
 *     #[ConstructorParam('bar')]
 *     public string $someBar;
 *
 *     #[ConstructorParam('baz')]
 *     public int $someBaz;
 * }
 * // Generated: new Example($bar, $baz)
 *
 * // Version 2: Same class, properties rearranged for readability
 * class Example {
 *     #[ConstructorParam('baz')]  // Moved this first
 *     public int $someBaz;
 *
 *     #[ConstructorParam('bar')]  // Now this is second
 *     public string $someBar;
 * }
 * // Generated: new Example($baz, $bar)  // ❌ BREAKS COMPATIBILITY!
 * ```
 *
 * ## Recommended Usage: Always Use Order Parameter
 *
 * To prevent compatibility issues, **always specify the `$order` parameter**:
 *
 * ```php
 * class Example {
 *     #[ConstructorParam('bar', 2)]    // Explicit order: position 2
 *     public string $someBar;
 *
 *     #[ConstructorParam('baz', 1)]    // Explicit order: position 1
 *     public int $someBaz;
 *
 *     #[ConstructorParam('flag', 3)]   // Explicit order: position 3
 *     public bool $someFlag;
 * }
 * // Always generates: new Example($baz, $bar, $flag)
 * // Order remains consistent regardless of property arrangement
 * ```
 *
 * ## How Ordering Works
 *
 * The framework uses smart sorting logic with backward compatibility:
 *
 * 1. **No `$order` parameters used**: Preserves original declaration order (backward compatible)
 * 2. **Any `$order` parameters used**: Enables order-aware sorting:
 *    - Parameters with explicit orders are sorted by their order values
 *    - Parameters without explicit orders are sorted alphabetically after ordered parameters
 *
 * ### Mixed Ordering Example
 *
 * ```php
 * class MixedExample {
 *     #[ConstructorParam('zulu')]      // No order - will be last alphabetically
 *     public $last;
 *
 *     #[ConstructorParam('alpha', 2)]  // Order 2 - second position
 *     public $second;
 *
 *     #[ConstructorParam('beta')]      // No order - will be after ordered params
 *     public $unordered;
 *
 *     #[ConstructorParam('gamma', 1)]  // Order 1 - first position
 *     public $first;
 * }
 * // Generated: new MixedExample($gamma, $alpha, $beta, $zulu)
 * //             ordered: ↑gamma(1), ↑alpha(2)  unordered: ↑beta, ↑zulu (alphabetical)
 * ```
 *
 * ## Basic Usage Without Order (Legacy)
 *
 * For simple cases where order doesn't matter, you can omit the order parameter:
 *
 * ```php
 * class SimpleExample {
 *     #[ConstructorParam('bar')]  // Uses property name 'bar' as parameter name
 *     public string $bar;
 *
 *     #[ConstructorParam('customName')]  // Maps property to parameter 'customName'
 *     public int $myProperty;
 *
 *     #[ConstructorParam()]  // Uses property name 'autoName' as parameter name
 *     public bool $autoName;
 * }
 * ```
 *
 * ## Best Practices
 *
 * 1. **Always use explicit ordering** for classes that may be refactored
 * 2. **Use consistent numbering** (1, 2, 3... or 10, 20, 30... for easier insertion)
 * 3. **Group related parameters** with similar order numbers
 * 4. **Document parameter purposes** in your class comments
 *
 * ## Backward Compatibility
 *
 * Existing code without `$order` parameters continues to work unchanged.
 * The new ordering feature only activates when at least one `$order` parameter is used.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ConstructorParam
{
    /**
     * @param string|null $name The name of the constructor parameter to map to this property.
     *                          If null, the property name itself is used.
     * @param int|null $order The explicit order position for this parameter in the constructor.
     *                        Parameters with explicit orders are sorted by order value.
     *                        Parameters without explicit orders are sorted alphabetically.
     */
    public function __construct(public ?string $name = null, public ?int $order = null)
    {
        // If $name is specified, that exact parameter name will be resolved.
        // Otherwise, the name of the property will be used as the parameter name.
        // If $order is specified, the parameter will be positioned according to that order.
    }
}