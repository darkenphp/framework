<?php

declare(strict_types=1);

namespace Darken\Console\Commands;

use Darken\Builder\CodeCompiler;
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

        $filescount = 0;
        try {

            $pages = [];

            foreach ($app->config->getBuildingFolders() as $folder) {
                foreach (FileHelper::findFiles($folder, ['only' => ['php']]) as $file) {
                    $input = new InputFile($file);

                    $compiler = new CodeCompiler();
                    $output = $compiler->compile($input);

                    $compileCodeOutput = new OutputCompiled($output->getCode(), $input, $app->config);

                    if ($this->createFile($compileCodeOutput)) {
                        $filescount++;


                        $polyfill = new OutputPolyfill($compileCodeOutput, $output);
                        $this->createFile($polyfill);

                        if ($input->isInDirectory($app->config->getPagesFolder())) {
                            $pages[] = $polyfill;
                        }
                    } else {
                        $app->stdOut("File {$input->getFileName()} failed to compile");
                    }
                }
            }

            /**
             * @var array<string, array{
             *     _children: array<string, mixed>,
             *     middlewares: array<string, mixed>,
             *     class?: string
             * }> $trie
             */
            $trie = [];
            foreach ($pages as $polyfill) {

                $page = new OutputPage($polyfill);

                // Insert route into Trie
                $node = &$trie;
                foreach ($page->getSegmentedTrieRoute() as $segment) {

                    if (!isset($node[$segment])) {
                        $node[$segment] = ['_children' => []];
                    }
                    $node = &$node[$segment]['_children'];
                }

                //$node['file_path'] = $polyfill->getBuildOutputFilePath();
                $node['class'] = $polyfill->getFullQualifiedClassName();
                $node['middlewares'] = $polyfill->compilerOutput->getMeta('middlewares');
            }


            $this->saveFile($app->config->getBuildOutputFolder() . '/routes.php', '<?php' . PHP_EOL . 'return ' . var_export($trie, true) . ';' . PHP_EOL);
        } catch (Throwable $e) {
            $app->stdOut('ERROR: '. $e->getMessage() .  ' | ' . $e->getFile() . ' | '  . $e->getTraceAsString());
        }

        $app->stdOut("Compiled {$filescount} files to {$app->config->getBuildOutputFolder()}");
    }

    private function createFile(FileSaveInterface $save): bool
    {
        FileHelper::ensureDirectory(dirname($save->getBuildOutputFilePath()));
        
        return $this->saveFile($save->getBuildOutputFilePath(), $save->getBuildOutputContent());
    }

    private function saveFile(string $file, string $content): bool
    {
        return file_put_contents($file, $content) !== false;
    }
}
