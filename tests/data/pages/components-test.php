<?php $layout = (new Tests\Build\data\components\Layout1('layoutarg1', 'layoutarg2'))->openSlot1(); ?>
<div class="slot1">Slot 1</div>
<?= new \Tests\Build\data\components\Alert('alert message'); ?>
<?php $layout->closeSlot1()->openNmdSlot2(); ?>
<div class="nmdSlot2">Named Slot 2</div>
<?= $layout->closeNmdSlot2()->render(); ?>
