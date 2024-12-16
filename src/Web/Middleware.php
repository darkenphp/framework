<?php

declare(strict_types=1);

namespace Darken\Web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Pre-processing logic
        // E.g., logging or modifying the request

        return $handler->handle($request);

        // Post-processing logic
        // E.g., adding headers or transforming the response
    }
}
