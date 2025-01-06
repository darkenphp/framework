<?php

declare(strict_types=1);

namespace Darken\Web;

use Darken\Code\Runtime;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PageHandler implements RequestHandlerInterface
{
    public function __construct(private RouteExtractor $route)
    {

    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->route->isMethodSupported()) {
            return new Response(405, [], 'Method not allowed');
        }

        if (!$this->route->isFound()) {
            return new Response(404, [], 'Page not found');
        }

        $runtimePage = Runtime::make($this->route->getClassName(), $this->route->getParams());

        $content = $runtimePage->render();

        if ($content instanceof ResponseInterface) {
            return $content;
        }

        return new Response(200, [
            'Content-Type' => 'text/html',
        ], $content);
    }
}
