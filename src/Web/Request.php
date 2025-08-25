<?php

declare(strict_types=1);

namespace Darken\Web;

use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

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

    public function getBodyParam(string $name, mixed $default = null): mixed
    {
        $raw = (string) $this->getBody();
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON in request body: ' . json_last_error_msg());
        }
        return $data[$name] ?? $default;
    }
}
