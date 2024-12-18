<?php

declare(strict_types=1);

namespace Darken\Middleware;

use Darken\Web\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * AuthenticationMiddleware checks for a valid authentication token in the request headers.
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * The name of the header that contains the authentication token.
     *
     * @var string
     */
    private string $authHeader;

    /**
     * The expected value of the authentication token.
     *
     * @var string
     */
    private string $expectedToken;

    /**
     * Constructor.
     *
     * @param string $authHeader     The header name to check for the token (e.g., 'Authorization').
     * @param string $expectedToken  The expected token value for authentication.
     */
    public function __construct(string $authHeader = 'Authorization', string $expectedToken = 'Bearer secret-token')
    {
        $this->authHeader = $authHeader;
        $this->expectedToken = $expectedToken;
    }

    /**
     * Process an incoming server request.
     *
     * @param ServerRequestInterface  $request The incoming server request.
     * @param RequestHandlerInterface $handler The request handler.
     *
     * @return ResponseInterface The response after processing.
     */
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
