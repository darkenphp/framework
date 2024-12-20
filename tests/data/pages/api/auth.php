<?php

use Darken\Code\InvokeResponseInterface;
use Darken\Enum\MiddlewarePosition;
use Darken\Middleware\AddCustomHeaderMiddleware;
use Darken\Middleware\AuthenticationMiddleware;
use Darken\Web\Response;

return new 
#[\Darken\Attributes\Middleware(AuthenticationMiddleware::class, ['authHeader' => 'Authorization', 'expectedToken' => 'FooBar'], MiddlewarePosition::BEFORE)]
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