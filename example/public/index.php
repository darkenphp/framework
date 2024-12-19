<?php
declare(strict_types=1);

use App\Config;
use Darken\Web\Application;

include __DIR__ . '/../vendor/autoload.php';

$config = new Config(
    rootDirectoryPath: dirname(__DIR__),
);

(new Application($config))
    ->run();