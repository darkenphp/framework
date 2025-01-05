<?php

use Darken\Code\InvokeResponseInterface;
use Darken\Enum\MiddlewarePosition;
use Darken\Middleware\CorsMiddleware;
use Darken\Web\Response;

return new 
#[\Darken\Attributes\Middleware(CorsMiddleware::class, [], MiddlewarePosition::BEFORE)]
class implements InvokeResponseInterface
{
    public function __invoke(): Response
    {
        return new Response(
            200,
            [],
            json_encode([
                'message' => 'auth-api'
            ]),
        );
    }
};