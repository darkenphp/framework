<?php

declare(strict_types=1);

namespace Darken\Code;

use Darken\Kernel;
use Darken\Web\Request;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * The context which ALL compile code runs and is injected into the __constructor as __construct(Runtime $runtime)
 *
 * lowest level of runtime is component runtime
 */
abstract class Runtime
{
    private array $data = [];

    /**
     * If the runtime is used as a string (using echo), the render() method is invoken.
     * If the render() method returns an instance of ResponseInterface, the body of the response is returned as string by default.
     */
    public function __toString(): string
    {
        return ($response = $this->render()) instanceof ResponseInterface ? $response->getBody()->getContents() : (string) $response;
    }

    /**
     * The path to the compiled file that will be executed from the render() method inside the runtime polyfill.
     */
    abstract public function renderFilePath(): string;

    /**
     * Exchange Bus between Runtime Polyfill and Compiled Code. Set data in the polyfill and access it in the compiled code.
     */
    public function setData(string $section, string $key, mixed $value): void
    {
        $this->data[$section] ??= [];
        $this->data[$section][$key] = $value;
    }

    /**
     * Exchange Bus between Runtime Polyfill and Compiled Code. Get data in the compiled code that was set in the polyfill.
     */
    public function getData(string $section, string $key, mixed $defaultValue = null): mixed
    {
        return $this->data[$section][$key] ?? $defaultValue;
    }

    /**
     * Access the Kernels DI Container Service
     */
    public function getContainer($className): object
    {
        return Kernel::getContainerService()->resolve($className);
    }

    /**
     * Helper method to access the Request object, resolved from the DI Container.
     */
    public function getRequest(): Request
    {
        return Kernel::getContainerService()->resolve(Request::class);
    }

    /**
     * Create a new runtime instances.
     *
     * This "helper" ensures an object of type runtime (self) is returned and therefore the
     * recommend way to create a new runtime instance.
     */
    public static function make(string $className, array $params = []): self
    {
        return Kernel::getContainerService()->createObject($className, $params);
    }

    /**
     * Renders the compiled file from renderFilePath() as string or ResponseInterface.
     */
    public function render(): string|ResponseInterface
    {
        $_file_ = $this->renderFilePath();

        if (!file_exists($_file_)) {
            throw new RuntimeException("File not found: $_file_");
        }

        if (!is_readable($_file_)) {
            throw new RuntimeException("File not readable: $_file_");
        }

        $_obInitialLevel_ = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        try {
            $x = require $_file_;
            $content = ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        }

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
