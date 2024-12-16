<?php

declare(strict_types=1);

namespace Darken\Web;

use Darken\Kernel;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

class Application extends Kernel
{
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

        $middleware = new Middleware();
        $handler = new Handler($this->config);

        $response = $middleware->process($request, $handler);
        //$this->findRoute($_SERVER['REQUEST_URI']);

        header('Content-Type: text/html; charset=UTF-8');
        echo $response->getBody();
    }
}
