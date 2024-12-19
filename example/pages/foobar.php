<?php

use App\Config;
use App\Test;
use Darken\Enum\MiddlewarePosition;
use Darken\Middleware\AddCustomHeaderMiddleware;

$page = new 
    #[\Darken\Attributes\Middleware(AddCustomHeaderMiddleware::class, ['name' => 'Key', 'value' => 'Bar'], MiddlewarePosition::BEFORE)] 
    class {

    #[\Darken\Attributes\Inject]
    public Config $byref;

    // below should also work without \App\Config::class since its defined in public Config <-- $full as typehinting prop
    #[\Darken\Attributes\Inject(\App\Config::class)]
    public Config $full;
    

    #[\Darken\Attributes\Inject(Test::class)]
    public Test $test;
};


use Build\components\Alert;
use Build\components\Layout;
$layout = new Layout('Index Seite');
?>
<?php $layout->openHead(); ?>
    <style>
        .info {
            border: 1px solid blue;
            padding: 20px;
        }
    </style>
<?php $layout->closeHead(); ?>
<?php $layout->openContent(); ?>
<h1><?= $page->test->getUpperCase(); ?></h1>
    <h2 class="info">Willkommen auf der Index Seite</h2>
    <p>
        Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five
    </p>
    <?= new Alert('I am componet without slots.'); ?>
<?php $layout->closeContent(); ?>
<?= $layout->render(); ?>