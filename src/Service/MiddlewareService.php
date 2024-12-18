<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Enum\MiddlewarePosition;
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

    /**
     * The final handler to execute if no middleware handles the request.
     *
     * @var RequestHandlerInterface
     */
    private RequestHandlerInterface $defaultHandler;

    /**
     * Constructor.
     *
     * @param RequestHandlerInterface $defaultHandler The final request handler.
     */
    public function __construct(RequestHandlerInterface $defaultHandler)
    {
        $this->defaultHandler = $defaultHandler;
    }

    /**
     * Adds a middleware to the stack.
     *
     * @param MiddlewareInterface    $middleware The middleware to add.
     * @param MiddlewarePosition     $position   The position to add the middleware.
     *
     * @return self
     */
    public function add(MiddlewareInterface $middleware, MiddlewarePosition $position = MiddlewarePosition::BEFORE): self
    {
        if ($position === MiddlewarePosition::BEFORE) {
            array_unshift($this->middlewares, $middleware);
        } else {
            $this->middlewares[] = $middleware;
        }

        return $this;
    }

    /**
     * Handles the incoming request by passing it through the middleware stack.
     *
     * @param ServerRequestInterface $request The incoming server request.
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = $this->defaultHandler;

        // Iterate through the middlewares in reverse order to build the chain.
        foreach (array_reverse($this->middlewares) as $middleware) {
            $handler = new class($middleware, $handler) implements RequestHandlerInterface {
                private MiddlewareInterface $middleware;

                private RequestHandlerInterface $handler;

                public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $handler)
                {
                    $this->middleware = $middleware;
                    $this->handler = $handler;
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
