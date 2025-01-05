<?php

use Darken\Enum\MiddlewarePosition;
use Darken\Middleware\CorsMiddleware;

$page = new 

#[\Darken\Attributes\Middleware(CorsMiddleware::class, [], MiddlewarePosition::AFTER)]
class {
    #[\Darken\Attributes\RouteParam]
    public string $id;
};
?>
pages/blogs/[[id]]/comments:<?= $page->id ?>