<?php

use Darken\Enum\MiddlewarePosition;
use Darken\Middleware\AddCustomHeaderMiddleware;

$page = new 

#[\Darken\Attributes\Middleware(AddCustomHeaderMiddleware::class, ['name' => 'X-Foo', 'value' => 'X-Bar'], MiddlewarePosition::AFTER)]
class {
    #[\Darken\Attributes\RouteParam]
    public string $id;
};
?>
pages/blogs/[[id]]/comments:<?= $page->id ?>