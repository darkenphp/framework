<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Enum\MiddlewarePosition;
use Darken\Web\PageHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareService implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $middlewares = [];

    private PageHandler $pageHandler;

    public function __construct(PageHandler $pageHandler)
    {
        $this->pageHandler = $pageHandler;
    }

    public function add(MiddlewareInterface $middleware, MiddlewarePosition $position): self
    {
        if ($position === MiddlewarePosition::BEFORE) {
            array_unshift($this->middlewares, $middleware);
        } else {
            $this->middlewares[] = $middleware;
        }

        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = $this->pageHandler;

        // Iterate through the middlewares in reverse order to build the chain.
        foreach (array_reverse($this->middlewares) as $middleware) {
            $handler = new class($middleware, $handler) implements RequestHandlerInterface {
                public function __construct(private MiddlewareInterface $middleware, private RequestHandlerInterface $handler)
                {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->middleware->process($request, $this->handler);
                }
            };
        }

        return $handler->handle($request);
    }
}
