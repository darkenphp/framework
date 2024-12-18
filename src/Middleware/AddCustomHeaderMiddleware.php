<?php

declare(strict_types=1);

namespace Darken\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AddCustomHeaderMiddleware implements MiddlewareInterface
{
    public function __construct(private string $name, private string $value)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Proceed to the next middleware or handler and get the response
        $response = $handler->handle($request);

        // Add the custom header to the response
        return $response->withHeader($this->name, $this->value);
    }
}
