<?php
$layout = new class {

    #[\Darken\Attributes\ConstructorParam]
    public $title;

    #[\Darken\Attributes\Slot]
    public $head;
    
    #[\Darken\Attributes\Slot]
    public $content;
};
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Darken Example: <?= $layout->title; ?></title>
        <?= $layout->head; ?>
    </head>
    <body>
        <div style="border:1px solid black; padding:20px;">
            <h1><?= $layout->title; ?></h1>

            <?= $layout->content; ?>
        </div>
    </body>
</html>