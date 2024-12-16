<?php

declare(strict_types=1);

namespace Darken\Console\Commands;

use Darken\Builder\CodeCompiler;
use Darken\Builder\Collection\Components;
use Darken\Builder\Collection\Pages;
use Darken\Builder\FileSaveInterface;
use Darken\Builder\InputFile;
use Darken\Builder\OutputCompiled;
use Darken\Builder\OutputPage;
use Darken\Builder\OutputPolyfill;
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

        try {

            /*
            $collections = [
                new Components(),
                new Pages(),
            ];
            */

            $pages = [];

            foreach ($app->config->getBuildingFolders() as $folder) {
                foreach (FileHelper::findFiles($folder, ['only' => ['php']]) as $file) {
                    $input = new InputFile($file);

                    $compiler = new CodeCompiler();
                    $output = $compiler->compile($input);

                    $compileCodeOutput = new OutputCompiled($output->code, $input, $app->config);

                    if ($this->createFile($compileCodeOutput)) {


                        $polyfill = new OutputPolyfill($compileCodeOutput, $output);
                        $this->createFile($polyfill);

                        if ($input->isInDirectory($app->config->getPagesFolder())) {
                            $pages[] = $compileCodeOutput;
                        }
                    } else {
                        $app->stdOut("File {$input->getFileName()} failed to compile");
                    }
                }
            }

            $trie = [];
            foreach ($pages as $file) {

                $page = new OutputPage($file);

                // Insert route into Trie
                $node = &$trie;
                foreach ($page->getSegmentedTrieRoute() as $segment) {

                    if (!isset($node[$segment])) {
                        $node[$segment] = ['_children' => []];
                    }
                    $node = &$node[$segment]['_children'];
                }

                if ($this->createFile($page)) {
                    $node['file_path'] = $page->getBuildOutputFilePath();
                    $node['class_name'] = $page->getFullQulieidNamespacedClassName();
                }
            }


            $this->saveFile($app->config->getBuildOutputFolder() . '/routes.php', '<?php' . PHP_EOL . 'return ' . var_export($trie, true) . ';' . PHP_EOL);
        } catch (Throwable $e) {
            $app->stdOut('ERROR: '. $e->getMessage() .  ' | ' . $e->getFile() . ' | '  . $e->getTraceAsString());
        }
    }

    private function createFile(FileSaveInterface $save): bool
    {
        // ensure
        FileHelper::ensureDirectory(dirname($save->getBuildOutputFilePath()));
        // save
        return $this->saveFile($save->getBuildOutputFilePath(), $save->getBuildOutputContent());
    }

    private function saveFile(string $file, string $content): bool
    {
        // Save the Trie structure to a file
        return file_put_contents($file, $content) !== false;
    }
}
