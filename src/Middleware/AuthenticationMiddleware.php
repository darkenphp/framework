<?php

declare(strict_types=1);

namespace Darken\Middleware;

use Darken\Web\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(private string $authHeader, private string $expectedToken)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Retrieve the authentication header from the request
        $authHeaderValue = $request->getHeaderLine($this->authHeader);

        // Check if the authentication token is present and valid
        if (empty($authHeaderValue) || $authHeaderValue !== $this->expectedToken) {
            // Return a 401 Unauthorized response
            return new Response(
                401,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Unauthorized'])
            );
        }

        // Authentication successful; proceed to the next middleware or handler
        return $handler->handle($request);
    }
}
