<?php
$component = new class {
    #[\Darken\Attributes\Param]
    public $message;
};
?>
<div class="alert"><?= $component->message ?></div>