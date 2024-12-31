<?php

use Darken\Attributes\ConstructorParam;
use Darken\Attributes\Inject;
use Darken\Attributes\Middleware;
use Darken\Attributes\PostParam;
use Darken\Attributes\QueryParam;
use Darken\Attributes\Slot;
use Darken\Middleware\CorsMiddlware;
use Tests\data\di\Db;

$class = new #[Middleware(CorsMiddlware::class)] class
{
    #[ConstructorParam]
    public string $init = 'init';
    #[Inject]
    public Db $db;
    #[PostParam]
    public string|null $post = 'post';
    #[QueryParam]
    public string $query = 'query';
    #[Slot]
    public string|null $slot;
};
?>
pages/params