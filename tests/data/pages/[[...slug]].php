<?php
$page = new class {
    #[\Darken\Attributes\RouteParam]
    public string $slug;
};
?>
pages/[[...slug]]:<?= $page->slug ?>