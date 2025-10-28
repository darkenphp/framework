<?php

use App\Test;
use Build\pages\testid;
use Darken\Attributes\Inject;
use Darken\Service\LogService;
use Darken\Service\RouteService;

$page = new class {
    #[Inject(LogService::class)]
    public LogService $log;

    #[Inject(Test::class)]
    public Test $test;

    #[Inject(RouteService::class)]
    public RouteService $route;

    public function __construct()
    {
        $this->log->alert('page alert');
    }
};
?>

<?php var_dump($page->route->url(testid::class, ['id' => 123])); ?>