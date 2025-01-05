<?php

declare(strict_types=1);

namespace Darken\Console\Commands;

use Darken\Console\Application;
use Darken\Console\CommandInterface;
use Yiisoft\Files\FileHelper;

/**
 * Build Watcher
 *
 * This class is used to define the watch command for the application. It watches the
 * building folders for changes and rebuilds the application whenever changes are detected.
 */
class Watch implements CommandInterface
{
    public function run(Application $app): void
    {
        $app->stdOut('Watching for changes...');

        $folders = $app->config->getBuildingFolders();
        $lastTime = FileHelper::lastModifiedTime(...$folders);

        // @phpstan-ignore-next-line
        while (true) {
            // Sleep a bit to avoid hogging CPU
            usleep(500000); // 0.5 seconds
            $nowTime = FileHelper::lastModifiedTime(...$folders);
            if ($nowTime > $lastTime) {
                $lastTime = $nowTime;
                $build = new Build();
                $build->clear = $app->getArgument('clear', false);
                $build->run($app);
                unset($build);
            }
        }
    }
}
