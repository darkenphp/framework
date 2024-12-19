<?php

use App\Config;

$component = new class {

    #[\Darken\Attributes\Inject]
    public Config $config;

    #[\Darken\Attributes\Param]
    public $message;
}
?>
<div style="border:1px solid red; padding:20px;">
    <?= $component->message; ?>
</div>