<?php

declare(strict_types=1);

namespace Darken\Console\Commands;

use Darken\Console\Application;
use Darken\Console\CommandInterface;
use Yiisoft\Files\FileHelper;

class Watch implements CommandInterface
{
    public function run(Application $app): void
    {
        $app->stdOut('Watching for changes...');
        $this->runWatch($app->config->getBuildingFolders(), $app);
    }

    /**
    * Watches the folders and rebuilds whenever changes are detected.
    */
    protected function runWatch(array $folders, Application $app): void
    {
        // Capture initial state
        $lastHashes = [];
        foreach ($folders as $folder) {
            $lastHashes[$folder] = $this->hashDirectory($folder);
        }

        // @phpstan-ignore-next-line
        while (true) {
            // Sleep a bit to avoid hogging CPU
            usleep(500000); // 0.5 seconds

            $changesDetected = false;
            foreach ($folders as $folder) {
                $currentHash = $this->hashDirectory($folder);
                if ($currentHash !== $lastHashes[$folder]) {
                    $changesDetected = true;
                    $lastHashes[$folder] = $currentHash;
                }
            }

            if ($changesDetected) {
                $app->stdOut('Changes detected.');
                $build = new Build();
                $build->run($app);
            }
        }
    }

    /**
     * Creates a hash representing the current state of a directory.
     * This function recursively scans the directory, collecting file paths and modification times.
     * It then returns a hash that changes whenever a file is added, removed, or modified.
     */
    protected function hashDirectory(string $directory): string
    {
        // todo use yii2 file helper
        if (!is_dir($directory)) {
            return '';
        }

        $files = FileHelper::findFiles($directory, ['only' => ['php']]);
        // Sort the file list to ensure stable ordering before hashing
        sort($files);

        $data = '';
        foreach ($files as $file) {
            $data .= $file . '|' . filemtime($file) . "\n";
        }

        return md5($data);
    }
}
