<?php

declare(strict_types=1);

namespace Darken\Console\Commands;

use Darken\Console\Application;
use Darken\Console\CommandInterface;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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

        // Continuous loop to check for changes
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

        $files = $this->getAllFiles($directory);
        // Sort the file list to ensure stable ordering before hashing
        sort($files);

        $data = '';
        foreach ($files as $file) {
            $data .= $file . '|' . filemtime($file) . "\n";
        }

        return md5($data);
    }

    /**
     * Recursively retrieves all file paths within a directory.
     */
    protected function getAllFiles(string $directory): array
    {
        // todo use yii2 file helper
        $result = [];
        $dirIterator = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $result[] = $item->getPathname();
            }
        }

        return $result;
    }
}
