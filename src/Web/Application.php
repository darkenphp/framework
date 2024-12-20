<?php

declare(strict_types=1);

namespace Darken\Web;

use Darken\Kernel;
use Darken\Service\MiddlewareService;
use Darken\Service\MiddlewareServiceInterface;
use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\CallbackHandler;
use Whoops\Handler\PrettyPageHandler;

class Application extends Kernel
{
    public function initalize(): void
    {
        if ($this->config->getDebugMode()) {
            $handler = new PrettyPageHandler();
        } else {
            $handler = new CallbackHandler(function (\Throwable $exception) {
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
                            <strong>Exception Message:</strong> {$exception->getMessage()}<br />
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
        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        $response = $this->handleServerRequest($creator->fromGlobals());

        $this->handleResponse($response);
    }

    private function handleServerRequest(ServerRequestInterface $request): ResponseInterface
    {
        // Instantiate the final handler
        $pageHandler = new PageHandler($this, $request->getUri()->getPath());

        // Instantiate the MiddlewareService with the final handler
        $middlewareService = new MiddlewareService($pageHandler);

        foreach ($pageHandler->getMiddlewares() as $middlewareConfig) {
            $className = $middlewareConfig['class'];
            $object = $this->getContainerService()->createObject($className, $middlewareConfig['params'] ?? []);
            $middlewareService->add($object, constant($middlewareConfig['position']));
        }

        if ($this->config instanceof MiddlewareServiceInterface) {
            $middlewareService = $this->config->middlewares($middlewareService);
        }

        // Handle the request through the middleware stack
        return $middlewareService->handle($request);
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
