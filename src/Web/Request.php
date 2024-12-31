<?php

declare(strict_types=1);

namespace Darken\Web;

use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class Request extends ServerRequest implements ServerRequestInterface
{
    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return $this->getQueryParams()[$name] ?? $default;
    }

    public function getPostParam(string $name, mixed $default = null): mixed
    {
        return $this->getParsedBody()[$name] ?? $default;
    }
}
