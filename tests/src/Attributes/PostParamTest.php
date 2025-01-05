<?php

namespace Tests\src\Attributes;

use Darken\Attributes\PostParam;
use Tests\TestCase;

class PostParamTest extends TestCase
{
    public function testConstructor()
    {
        $postParam = new PostParam('test');
        $this->assertInstanceOf(PostParam::class, $postParam);
    }
}