<?php
$class = new class {
    #[\Darken\Attributes\RouteParam()]
    public string $file;
};
?>
<h1>Das ist wildcard file</h1>
<h2><?= $class->file ?></h2>