<?php

declare(strict_types=1);

namespace Darken\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class RouteParam
{
    public function __construct(public ?string $name = null)
    {
        // if defined, the name will be taken to resolve the route param
        // which means you can use another argument name for the actuall
        // parameter name.
        //
        // For example if you have a route like this:
        // /users/{id}
        //
        // You can assign the route param "id" ass followed to another param name
        //
        // #[\Darken\Attributes\RouteParam('id')]
        // public string $userId;
        //
        // this makes in the code more clear, you are working with an userId, will the [[id]] might be more clear in
        // the file based routing
    }
}
