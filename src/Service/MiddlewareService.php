<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Enum\MiddlewarePosition;
use Psr\Http\Server\MiddlewareInterface;

final class MiddlewareService
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $middlewares = [];

    public function __construct(private ContainerService $containerService)
    {

    }

    public function register(MiddlewareInterface|string $middleware, MiddlewarePosition $position): self
    {
        $item = [
            'container' => $middleware,
            'position' => $position,
        ];

        if ($position === MiddlewarePosition::BEFORE) {
            array_unshift($this->middlewares, $item);
        } else {
            $this->middlewares[] = $item;
        }

        return $this;
    }

    public function remove(MiddlewareInterface $middleware): self
    {
        foreach ($this->middlewares as $key => $item) {
            if ($this->ensureContainer($item['container']) === $middleware) {
                unset($this->middlewares[$key]);
            }
        }

        return $this;
    }

    public function getChain(): array
    {
        $chain = [];
        foreach (array_reverse($this->middlewares) as $middleware) {
            $chain[] = $this->ensureContainer($middleware['container']);
        }

        return $chain;
    }

    public function retrieve(): array
    {
        return $this->middlewares;
    }

    private function ensureContainer(mixed $container): MiddlewareInterface
    {
        return $this->containerService->ensure($container, MiddlewareInterface::class);
    }
}
