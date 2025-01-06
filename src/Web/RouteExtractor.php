<?php

declare(strict_types=1);

namespace Darken\Web;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class RouteExtractor
{
    private array $trie = [];

    private false|array $node = false;

    private $isRouteMatch = false;

    private $isMethodMatch = true;

    public function __construct(private Application $app, ServerRequestInterface $request)
    {
        $routesFile = $this->getRoutesFile();

        if (file_exists($routesFile) && !is_readable($routesFile)) {
            throw new RuntimeException('Routes file is not readable');
        }

        if (file_exists($routesFile)) {
            $this->trie = include($routesFile);
        }

        $node = $this->findRouteNode($request->getUri()->getPath(), $this->trie);

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

    private function getRoutesFile(): string
    {
        return $this->app->config->getBuildOutputFolder() . DIRECTORY_SEPARATOR . 'routes.php';
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

    private function findRouteNode(string $url, array $trie): false|array
    {
        $segments = explode('/', trim($url, '/'));
        $hasWildCardMatch = false;
        $mustMatchFirst = false;
        $node = $trie;
        $params = [];

        // if there is only 1 segement and it is empty, then it is the root
        if (count($segments) === 1 && empty($segments[0])) {
            $segments = ['index'];
            if (array_key_exists('index', $node)) {
                return [$node['index']['_children'], $params];
            }
            $mustMatchFirst = true;
        } else {
            $segments[] = 'index';
        }

        foreach ($segments as $index => $segment) {

            // Try exact match first
            if (isset($node[$segment])) {
                $node = $node[$segment]['_children'];
                continue;
            }

            // see if node matches regex
            foreach ($node as $key => $child) {
                // Example dynamic route key: <id:[a-zA-Z0-9\-]+> or <slug:.+>
                if (strpos($key, '<') === 0 && strpos($key, '>') === strlen($key) - 1) {
                    $pattern = substr($key, 1, -1);
                    [$name, $regex] = explode(':', $pattern);
                    if ($regex === '.+') {
                        if (preg_match("/^$regex$/", $segment)) {
                            $slices = array_slice($segments, $index);
                            // if the last slice is "index" then remove it
                            if (end($slices) === 'index') {
                                array_pop($slices);
                            }
                            $params[$name] = implode('/', $slices);
                            $node = $child['_children'];
                            $hasWildCardMatch = true;
                            continue 2;
                        }
                    } elseif (preg_match("/^$regex$/", $segment)) {
                        $params[$name] = $segment;
                        $node = $child['_children'];
                        continue 2;
                    }
                }
            }

            // its the last index and we are at the end
            // if the segment is index and we have a page, then we are good
            // its an index page which is equals "" path.
            if ($segment == 'index' && $node) {
                continue;
            }

            if (!$hasWildCardMatch) {
                return false;
            }
        }

        if ($mustMatchFirst && !$hasWildCardMatch) {
            return false;
        }

        return [$node, $params];
    }
}
