<?php

declare(strict_types=1);

namespace Darken\Web;

use Darken\Kernel;
use Darken\Service\MiddlewareService;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Whoops\Handler\CallbackHandler;
use Whoops\Handler\PrettyPageHandler;

/**
 * Web Application
 *
 * This class is used to define the web application.
 */
class Application extends Kernel
{
    public function initalize(): void
    {
        if ($this->config->getDebugMode()) {
            $handler = new PrettyPageHandler();
        } else {
            $handler = new CallbackHandler(function (Throwable $exception) {
                echo <<<HTML
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <title>Error</title>
                    </head>
                    <body style="font-family: Arial, sans-serif; background-color: #f9f9f9; margin: 20px;">
                        <h1 style="color: #333333;">An error occurred while processing your request</h1>
                        <div style="
                            margin-top: 20px;
                            padding: 15px;
                            background-color: #ffecec;
                            border-left: 6px solid #f44336;
                            border-radius: 4px;
                        ">
                            {$exception->getMessage()}<br />
                            <strong>Error Code:</strong> {$exception->getCode()}
                        </div>
                    </body>
                    </html>
                    HTML;
            });
        }
        $this->whoops->pushHandler($handler);
        $this->whoops->register();
    }

    public function run(): void
    {
        $psr17Factory = new Psr17Factory();
        $serverRequestFactory = new class() implements ServerRequestFactoryInterface {
            public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
            {
                return new Request($method, $uri, [], null, '1.1', $serverParams);
            }
        };
        $creator = new ServerRequestCreator(
            $serverRequestFactory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        $response = $this->handleServerRequest($creator->fromGlobals());

        $this->handleResponse($response);
    }

    private function handleServerRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->getContainerService()->register(Request::class, $request);

        // Instantiate the final handler
        $pageHandler = new PageHandler($this, $request->getUri()->getPath());

        $currentRequestHttpMethod = $request->getMethod();
        $allowedHttpMethods = $pageHandler->getMethods();

        if (count($allowedHttpMethods) > 0 && !in_array($currentRequestHttpMethod, $allowedHttpMethods)) {
            return new Response(405, [], 'Method Not Allowed');
        }

        $temporaryMiddlewares = [];
        foreach ($pageHandler->getMiddlewares() as $middlewareConfig) {
            $className = $middlewareConfig['class'];
            $object = $this->getContainerService()->create($className, $middlewareConfig['params'] ?? []);
            $this->getMiddlewareService()->register($object, constant($middlewareConfig['position']));
            $temporaryMiddlewares[] = $object;
        }

        $requestHandler = new class($pageHandler, $this->getMiddlewareService()) implements RequestHandlerInterface {
            public function __construct(private PageHandler $pageHandler, private MiddlewareService $middlewareService)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $handler = $this->pageHandler;
                // Iterate through the middlewares in reverse order to build the chain.
                foreach ($this->middlewareService->getChain() as $middleware) {
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
        };

        // Handle the request through the middleware stack
        $response = $requestHandler->handle($request);

        foreach ($temporaryMiddlewares as $middleware) {
            $this->getMiddlewareService()->remove($middleware);
            $this->getContainerService()->remove(get_class($middleware));
        }

        unset($requestHandler, $pageHandler, $temporaryMiddlewares);

        return $response;
    }

    private function handleResponse(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        echo $response->getBody();
    }
}
