<?php

namespace Tests\src\Builder;

use Darken\Builder\FileBuildProcess;
use Tests\TestCase;

class OutputPageTest extends TestCase
{
    public function testExtractMethods()
    {
        $config = $this->createConfig();

        $build = new FileBuildProcess($config->getRootDirectoryPath(). DIRECTORY_SEPARATOR . 'data/pages/users/index.post.php', $config);
        $page = $build->getPageOutput($config);

        $this->assertSame(['POST'], $page->getVerbs());
        $this->assertSame(['users', 'index'], $page->getSegmentedTrieRoute());

        $build = new FileBuildProcess($config->getRootDirectoryPath(). DIRECTORY_SEPARATOR . 'data/pages/users/index.get.php', $config);
        $page = $build->getPageOutput($config);

        $this->assertSame(['GET'], $page->getVerbs());
        $this->assertSame(['users', 'index'], $page->getSegmentedTrieRoute());

        $build = new FileBuildProcess($config->getRootDirectoryPath(). DIRECTORY_SEPARATOR . 'data/pages/params.php', $config);
        $page = $build->getPageOutput($config);

        $this->assertSame(['*'], $page->getVerbs());
        $this->assertSame(['params'], $page->getSegmentedTrieRoute());
    }
}