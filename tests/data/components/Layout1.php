<?php

use Tests\data\di\Db;

$class = new class {
    #[\Darken\Attributes\Param]
    public string $arg1;

    #[\Darken\Attributes\Param('nmdArgu2')]
    public string $namedArg2;

    #[\Darken\Attributes\Slot]
    public string $slot1;

    #[\Darken\Attributes\Slot('nmdSlot2')]
    public string $slot2;

    #[\Darken\Attributes\Inject]
    public Db $db1;

    #[\Darken\Attributes\Inject]
    public \Tests\data\di\Db $db2;

    #[\Darken\Attributes\Inject(Db::class)]
    public Db $db3;

    public function upperArg1() : string
    {
        return strtoupper($this->arg1);
    }
}
?>
<h1><?= $class->arg1 ?></h1>
<h1><?= $class->upperArg1() ?></h1>
<h2><?= $class->namedArg2 ?></h2>
<div><?= $class->slot1 ?></div>
<div><?= $class->slot2 ?></div>
<div><?= $class->db1->getUpperDsn() ?></div>
<div><?= $class->db2->getUpperDsn() ?></div>
<div><?= $class->db3->getUpperDsn() ?></div>