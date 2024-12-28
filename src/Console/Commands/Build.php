<?php

declare(strict_types=1);

namespace Darken\Console\Commands;

use Darken\Builder\FileSaveInterface;
use Darken\Builder\OutputPage;
use Darken\Console\Application;
use Darken\Console\CommandInterface;
use Throwable;
use Yiisoft\Files\FileHelper;

class Build implements CommandInterface
{
    public $clear = false;

    public function run(Application $app): void
    {
        if ($this->clear) {
            FileHelper::clearDirectory($app->config->getBuildOutputFolder());
        }

        $filescount = 0;
        try {

            $pages = [];

            foreach ($app->config->getBuildingFolders() as $folder) {
                foreach (FileHelper::findFiles($folder, ['only' => ['php']]) as $file) {

                    $buildProcess = new FileBuildProcess($file, $app->config);

                    $processed = true;
                    foreach ($buildProcess->getFilesToSaveSequenze() as $save) {
                        if (self::createFile($save)) {
                            $filescount++;
                        } else {
                            // which should delete both files from the sequenze
                            $app->stdOut("File {$save->getBuildOutputFilePath()} failed to compile");
                            $processed = false;
                        }
                    }

                    if ($processed && $buildProcess->getIsPage()) {
                        $pages[] = $buildProcess->getPageOutput();
                    }
                }
            }

            /**
             * @var array<string, array{
             *     _children: array<string, mixed>,
             *     class?: string
             * }> $trie
             */
            $trie = [];
            foreach ($pages as $page) {
                /** @var OutputPage $page */
                $node = &$trie;
                foreach ($page->getSegmentedTrieRoute() as $segment) {
                    if (!isset($node[$segment])) {
                        $node[$segment] = ['_children' => []];
                    }
                    $node = &$node[$segment]['_children'];
                }
                $node = [...$node, ...$page->getNodeData()];
            }

            ksort($trie);

            $this->saveFile($app->config->getBuildOutputFolder() . '/routes.php', '<?php' . PHP_EOL . 'return ' . var_export($trie, true) . ';' . PHP_EOL);
        } catch (Throwable $e) {
            $errorMessage = $app->stdTextRed('ERROR: ') . $e->getMessage() . PHP_EOL .
            $app->stdTextYellow('File: ') . $e->getFile() . ' on line ' . $e->getLine() . PHP_EOL .
            $app->stdTextYellow('Trace:') . PHP_EOL . $e->getTraceAsString();
            $app->stdOut($errorMessage);
        }

        $compiledText = $app->stdTextGreen('Compiled ') . $app->stdTextYellow("{$filescount}") . ' files to ' . $app->stdTextYellow("{$app->config->getBuildOutputFolder()}");
        $app->stdOut($compiledText);
    }

    public static function createFile(FileSaveInterface $save): bool
    {
        FileHelper::ensureDirectory(dirname($save->getBuildOutputFilePath()));

        return self::saveFile($save->getBuildOutputFilePath(), $save->getBuildOutputContent());
    }

    public static function saveFile(string $file, string $content): bool
    {
        return file_put_contents($file, $content) !== false;
    }
}
