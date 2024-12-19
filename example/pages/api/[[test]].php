<?php
$class = new class {
    #[\Darken\Attributes\RouteParam]
    public string $test;
}
?>

<h1>yes: <?= $class->test; ?></h1>