<?php

namespace benchmark;

use Darken\Web\Application;
use Tests\TestConfig;

class WebBench {
 
    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchApplicationResolver()
    {
        $config = new TestConfig(
            rootDirectoryPath: dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests',
            pagesFolder: 'data/pages',
            builderOutputFolder: '.build',
            componentsFolder: 'data/components',
        );

        $web = new Application($config);
        ob_start();
        $web->run();
        ob_end_clean();
    }
}