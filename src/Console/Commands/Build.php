<?php

declare(strict_types=1);

namespace Darken\Console\Commands;

use Darken\Builder\ExtensionFile;
use Darken\Builder\FileBuildProcess;
use Darken\Builder\FileSaveInterface;
use Darken\Builder\OutputPage;
use Darken\Config\PagesConfigInterface;
use Darken\Console\Application;
use Darken\Console\CommandInterface;
use Darken\Enum\ConsoleExit;
use Darken\Events\AfterBuildEvent;
use Throwable;
use Yiisoft\Files\FileHelper;

/**
 * Compile Builder
 *
 * This class is used to define the build command for the console application. It
 * is used to compile the files in the building folders and save them to the build
 * output folder. It also creates the routes file for the application if the config
 * implements the PagesConfigInterface.
 */
class Build implements CommandInterface
{
    public function run(Application $app): ConsoleExit
    {
        if ($app->getArgument('clear', false)) {
            FileHelper::clearDirectory($app->config->getBuildOutputFolder());
        }

        $filescount = 0;
        try {

            $pages = [];
            $files = [];

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

                    if ($processed && $app->config instanceof PagesConfigInterface && $buildProcess->getIsPage()) {
                        $pages[] = $buildProcess->getPageOutput($app->config);
                    }

                    $files[] = $buildProcess;
                }
            }

            if ($app->config instanceof PagesConfigInterface) {
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
                        ksort($node);
                        $node = &$node[$segment]['_children'];
                    }

                    // Initialize the 'methods' key if it doesn't exist
                    if (!isset($node['methods'])) {
                        $node['methods'] = [];
                    }

                    $verbs = $page->getVerbs();

                    foreach ($verbs as $verb) {
                        $method = strtoupper($verb);
                        $node['methods'][$method] = $page->getNodeData();
                    }
                }

                // Sort methods arrays to ensure consistent ordering
                $this->sortMethodsRecursively($trie);
                ksort($trie);
                $this->saveFile($app->config->getBuildOutputFolder() . '/routes.php', '<?php' . PHP_EOL . 'return ' . var_export($trie, true) . ';' . PHP_EOL);
            }

        } catch (Throwable $e) {
            $errorMessage = $app->stdTextRed('ERROR: ') . $e->getMessage() . PHP_EOL .
            $app->stdTextYellow('File: ') . $e->getFile() . ' on line ' . $e->getLine() . PHP_EOL .
            $app->stdTextYellow('Trace:') . PHP_EOL . $e->getTraceAsString();
            $app->stdOut($errorMessage);
        }

        $compiledText = $app->stdTextGreen('✓') . ' Compiled ' . $filescount . ' files to ' . $app->config->getBuildOutputFolder();
        $app->stdOut($compiledText);

        $this->createFile(new ExtensionFile($files, $app));

        $app->getEventService()->dispatch(new AfterBuildEvent($app));

        return ConsoleExit::SUCCESS;
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

    /**
     * Recursively sort methods arrays in the trie to ensure consistent ordering
     *
     * @param array<string, mixed> $trie
     */
    private function sortMethodsRecursively(array &$trie): void
    {
        foreach ($trie as &$node) {
            if (!is_array($node)) {
                continue;
            }

            if (isset($node['methods'])) {
                ksort($node['methods']);
            }

            if (isset($node['_children'])) {
                $this->sortMethodsRecursively($node['_children']);
            }
        }
    }
}
