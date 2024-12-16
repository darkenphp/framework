<?php

declare(strict_types=1);

namespace Darken\Code;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

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

    /*
    private static bool $bufferStarted = false;

    public static function start(): void
    {
        if (self::$bufferStarted) {
            throw new RuntimeException('Output buffering has already started.');
        }
        ob_start();
        self::$bufferStarted = true;
    }

    public static function end(): string
    {
        if (!self::$bufferStarted) {
            throw new RuntimeException('Output buffering has not started.');
        }
        self::$bufferStarted = false;
        return ob_get_clean();
    }
        */

    public function render(array $_php_vars = []): string|ResponseInterface
    {
        ob_start();
        extract($_php_vars);
        $x = include($this->renderFilePath());
        $content = ob_get_clean();

        // Now $content contains anything that was directly echoed in the included file.
        // $x contains the return value of the included file.

        // If the return value is an object and callable (like your InvokeInterface class), invoke it:
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
