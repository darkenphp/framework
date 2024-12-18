<?php

declare(strict_types=1);

namespace Darken\Web;

use Darken\Kernel;
use Darken\Service\MiddlewareService;
use Darken\Service\MiddlewareServiceInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

class Application extends Kernel
{
    public function initalize(): void
    {
        if ($this->config->getDebugMode()) {
            $this->whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
            $this->whoops->register();
        }
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
        $request = $creator->fromGlobals();

        /*
        $middleware = new Middleware();
        $handler = new Handler($this->config);

        $response = $middleware->process($request, $handler);
        */
        // Instantiate the final handler
        $finalHandler = new Handler($this);

        // Instantiate the MiddlewareService with the final handler
        $middlewareService = new MiddlewareService($finalHandler);

        if ($this->config instanceof MiddlewareServiceInterface) {
            $middlewareService = $this->config->middlwares($middlewareService);
        }

        // Handle the request through the middleware stack
        $response = $middlewareService->handle($request);

        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        echo $response->getBody();
    }
}
