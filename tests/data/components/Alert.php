<?php
$component = new class {
    #[\Darken\Attributes\ConstructorParam]
    public $message;
};
?>
<div class="alert"><?= $component->message ?></div>