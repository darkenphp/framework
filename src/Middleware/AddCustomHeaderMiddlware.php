<?php

declare(strict_types=1);

namespace Darken\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * AddCustomHeaderMiddleware adds a custom header to the outgoing response.
 */
class AddCustomHeaderMiddleware implements MiddlewareInterface
{
    /**
     * The name of the header to add.
     *
     * @var string
     */
    private string $headerName;

    /**
     * The value of the header to add.
     *
     * @var string
     */
    private string $headerValue;

    /**
     * Constructor.
     *
     * @param string $headerName  The name of the header to add.
     * @param string $headerValue The value of the header to add.
     */
    public function __construct(string $headerName = 'X-Custom-Header', string $headerValue = 'MyApp')
    {
        $this->headerName = $headerName;
        $this->headerValue = $headerValue;
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
        // Proceed to the next middleware or handler and get the response
        $response = $handler->handle($request);

        // Add the custom header to the response
        return $response->withHeader($this->headerName, $this->headerValue);
    }
}
