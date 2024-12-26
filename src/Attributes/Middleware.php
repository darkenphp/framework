<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;
use Darken\Enum\MiddlewarePosition;

/**
 * An attribute used to attach a middleware to a class. This attribute can be repeated
 * multiple times on the same class to register multiple middlewares.
 * 
 * Example usage:
 *
 * ```php
 * use Darken\Enum\MiddlewarePosition;
 * 
 * #[Middleware(MyCustomMiddleware::class, ['option' => 'value'], MiddlewarePosition::AFTER)]
 * class SomeController
 * {
 *     // Controller logic...
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Middleware
{
    /**
     * @param string $class Fully-qualified class name of the middleware.
     * @param array<string,mixed> $params Additional parameters to pass to the middleware upon creation.
     * @param MiddlewarePosition $position Defines whether the middleware should run BEFORE or AFTER  the main execution flow.
     */
    public function __construct(
        public string $class,
        public array $params = [],
        public MiddlewarePosition $position = MiddlewarePosition::BEFORE
    ) {
        // ...
    }
}
