<?php

declare(strict_types=1);

namespace Tests\Web;

use Tests\TestCase;
use Darken\Web\Request;

class RequestTest extends TestCase
{
    public function testGetBodyParamReturnsValueWhenJsonBodyProvided()
    {
        $json = json_encode(['foo' => 'bar', 'num' => 42]);

        // Create a server request and set the body
        $request = $this->createServerRequest('/test', 'POST');
        $stream = \Nyholm\Psr7\Stream::create($json);
        $request = $request->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

    $this->assertInstanceOf(Request::class, $request);

    /** @var \Darken\Web\Request $request */
    $this->assertSame('bar', $request->getBodyParam('foo'));
    $this->assertSame(42, $request->getBodyParam('num'));
    }

    public function testGetBodyParamReturnsDefaultWhenMissing()
    {
        $json = json_encode(['present' => 'yes']);

        $request = $this->createServerRequest('/test', 'POST');
        $stream = \Nyholm\Psr7\Stream::create($json);
        $request = $request->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

    /** @var \Darken\Web\Request $request */
    $this->assertNull($request->getBodyParam('missing'));
    $this->assertSame('default', $request->getBodyParam('missing', 'default'));
    }

    public function testGetBodyParamThrowsOnInvalidJson()
    {
        $this->expectException(\RuntimeException::class);

        $invalidJson = '{bad json}';

        $request = $this->createServerRequest('/test', 'POST');
        $stream = \Nyholm\Psr7\Stream::create($invalidJson);
        $request = $request->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

    // This should throw
    /** @var \Darken\Web\Request $request */
    $request->getBodyParam('anything');
    }
}
