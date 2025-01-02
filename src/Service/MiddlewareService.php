<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Enum\MiddlewarePosition;
use Psr\Http\Server\MiddlewareInterface;

class MiddlewareService
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware, MiddlewarePosition $position): self
    {
        $item = [
            'object' => $middleware,
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
            if ($item['object'] === $middleware) {
                unset($this->middlewares[$key]);
            }
        }

        return $this;
    }

    public function getChain(): array
    {
        $chain = [];
        foreach (array_reverse($this->middlewares) as $middleware) {
            $chain[] = $middleware['object'];
        }

        return $chain;
    }

    public function retrieve(): array
    {
        return $this->middlewares;
    }
}
