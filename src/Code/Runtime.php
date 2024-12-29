<?php

declare(strict_types=1);

namespace Darken\Code;

use Darken\Kernel;
use Darken\Web\Request;
use Exception;
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

    public function __toString(): string
    {
        $response = $this->render();

        return $response instanceof ResponseInterface ? $response->getBody()->getContents() : $response;
    }

    abstract public function renderFilePath(): string;

    public function setData(string $section, string $key, mixed $value): void
    {
        if (!isset($this->data[$section])) {
            $this->data[$section] = [];
        }
        $this->data[$section][$key] = $value;
    }

    public function getData(string $section, string $key, mixed $defaultValue = null): mixed
    {
        return $this->data[$section][$key] ?? $defaultValue;
    }

    public function getContainer($className): object
    {
        return Kernel::getContainerService()->resolve($className);
    }

    public function getQueryParam(string $name): string|null
    {
        return $this->getRequest()->getQueryParams()[$name] ?? null;
    }

    public function getRequest(): Request
    {
        return Kernel::getContainerService()->resolve(Request::class);
    }

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
        } catch (Exception $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
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
