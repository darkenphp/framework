<?php

use App\Test;
use Darken\Attributes\Inject;
use Darken\Service\LogService;

$page = new class {
    #[Inject(LogService::class)]
    public LogService $log;

    #[Inject(Test::class)]
    public Test $test;

    public function __construct()
    {
        $this->log->alert('hey');
    }
};
?>

<?php var_dump($page->log->getLogs()); ?>