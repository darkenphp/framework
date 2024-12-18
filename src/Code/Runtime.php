<?php

declare(strict_types=1);

namespace Darken\Code;

use Darken\Kernel;
use Psr\Http\Message\ResponseInterface;

/**
 * The context which ALL compile code runs and is injected into the __constructor as __construct(Runtime $runtime)
 *
 * lowest level of runtime is component runtime
 */
abstract class Runtime
{
    private array $routeParams = [];

    private array $argumentParams = [];

    private array $slots = [];

    public function __toString()
    {
        return $this->render();
    }

    abstract public function renderFilePath(): string;

    public function setRouteParams(array $routeParams): void
    {
        $this->routeParams = $routeParams;
    }

    public function getRouteParam(string $name): string|null
    {
        return $this->routeParams[$name] ?? null;
    }

    public function setArgumentParam(string $name, string $value): void
    {
        $this->argumentParams[$name] = $value;
    }

    public function getArgumentParam(string $name): string|null
    {
        return $this->argumentParams[$name] ?? null;
    }

    public function setSlot(string $name, string $value): void
    {
        $this->slots[$name] = $value;
    }

    public function getSlot(string $name): string|null
    {
        return $this->slots[$name] ?? null;
    }

    public function getContainer($className): object
    {
        return Kernel::resolveContainer($className);
    }

    public function render(): string|ResponseInterface
    {
        ob_start();
        //extract($_php_vars);
        $x = include($this->renderFilePath());
        $content = ob_get_clean();

        if (is_object($x) && is_callable($x)) {

            $response = $x();
            if ($response instanceof ResponseInterface) {
                return $response;
            }

            $content .= $response;
        }

        // If the return value is an object that can be cast to a string (__toString):
        elseif (is_object($x) && method_exists($x, '__toString')) {
            $content .= $x;
        }
        // If the return value is a scalar other than the default '1':
        elseif (is_scalar($x) && $x !== 1) {
            $content .= $x;
        }

        return $content;
    }
}
