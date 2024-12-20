<?php
$page = new class {
    #[\Darken\Attributes\RouteParam]
    public string $id;
};
?>
pages/blogs/[[id]]:<?= $page->id ?>