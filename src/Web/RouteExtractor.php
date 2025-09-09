<?php

declare(strict_types=1);

namespace Darken\Web;

use Darken\Service\RouteService;
use Psr\Http\Message\ServerRequestInterface;

class RouteExtractor
{
    private false|array $node = false;

    private $isRouteMatch = false;

    private $isMethodMatch = true;

    public function __construct(private RouteService $routeService, ServerRequestInterface $request)
    {
        $node = $this->routeService->findRouteNode($request->getUri()->getPath());

        if ($node === false) {
            return;
        }

        $this->isRouteMatch = true;

        $this->node = $this->findMethod($node, $request->getMethod());
    }

    public function isFound(): bool
    {
        return $this->isRouteMatch && $this->getClassName() !== false;
    }

    public function getClassName(): string|false
    {
        return $this->node ? ($this->node[0]['class'] ?? false) : false;
    }

    public function getParams(): array
    {
        return $this->node ? $this->node[1] : [];
    }

    public function getMiddlewares(): array
    {
        return $this->node ? ($this->node[0]['middlewares'] ?? []) : [];
    }

    public function isMethodSupported(): bool
    {
        return $this->isMethodMatch;
    }

    private function findMethod(array $node, string $method): false|array
    {
        $methods = $node[0]['methods'] ?? [];

        if (array_key_exists($method, $methods)) {
            return [$methods[$method], $node[1]];
        }

        if (array_key_exists('*', $methods)) {
            return [$methods['*'], $node[1]];
        }

        $this->isMethodMatch = false;

        return false;
    }
}
