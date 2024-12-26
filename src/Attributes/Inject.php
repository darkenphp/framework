<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;

/**
 * An attribute that marks a property for dependency injection from a container.
 *
 * If a custom `$name` is provided, it will be used as the identifier to look up
 * the dependency in the container. If `$name` is omitted or `null`, the property's
 * own name will be used as the identifier.
 *
 * Example usage:
 *
 * ```php
 * #[Inject('logger')]
 * public LoggerInterface $myLogger;
 *
 * // In this case, the dependency named 'logger' in the container is injected
 * // into $myLogger.
 *
 * #[Inject]
 * public CacheInterface $cache;
 *
 * // Here, if no $name is specified, the property name "cache" is used as
 * // the identifier to retrieve the dependency from the container.
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Inject
{
    /**
     * @param string|null $name The identifier in the container to inject.
     *                          If null, the property name is used.
     */
    public function __construct(public ?string $name = null)
    {
    }
}
