<?php

declare(strict_types=1);

namespace Darken\Service;

use Darken\Config\ConfigInterface;
use InvalidArgumentException;

final class RouteService
{
    /**
     * The compiled route trie from routes.php
     * @var array<string,mixed>
     */
    private array $trie = [];

    public function __construct(private ConfigInterface $config)
    {
        $routesFile = $this->getRoutesFile();

        if (file_exists($routesFile) && !is_readable($routesFile)) {
            throw new InvalidArgumentException(sprintf('Routes file "%s" is not readable', $routesFile));
        }

        if (file_exists($routesFile)) {
            $trie = include $routesFile;
            if (!is_array($trie)) {
                throw new InvalidArgumentException(sprintf('Routes file "%s" must return an array.', $routesFile));
            }
            $this->trie = $trie;
        }
    }

    /**
     * Build a URL path for a compiled page class.
     *
     * @param string $class  Fully-qualified compiled page class (e.g. Build\pages\blogs\slug\api)
     * @param array  $params Route params used to fill dynamic segments. Extra params become query string.
     * @param string $method HTTP method to match against route methods (defaults to GET; '*' also matches).
     *
     * @return string URL path, query string appended for unused params.
     */
    public function create(string $class, array $params = [], string $method = 'GET'): string
    {
        $method = strtoupper($method);

        // Depth-first search for the class; returns the stack of segment keys if found.
        $stack = $this->findPathForClass($this->trie, $class, $method, []);
        if ($stack === null) {
            throw new InvalidArgumentException("No route found for class {$class} (method {$method}).");
        }

        // Build concrete segments from stack (replace <name:regex> with $params[name])
        $usedParamNames = [];
        $builtSegments  = [];

        foreach ($stack as $i => $segmentKey) {
            // Skip trailing "index" segments in URLs (e.g. /blogs not /blogs/index)
            $isLast = ($i === count($stack) - 1);
            if ($segmentKey === 'index' && $isLast) {
                continue;
            }

            // Handle segments that may contain 0..n placeholders like <name:regex>, even when embedded in static text
            if (preg_match_all('/<([^:>]+):([^>]+)>/', $segmentKey, $matches, PREG_SET_ORDER)) {
                $cursor   = 0;
                $rendered = '';

                foreach ($matches as $m) {
                    [$full, $name, $regex] = $m;
                    $pos = strpos($segmentKey, $full, $cursor);
                    if ($pos === false) {
                        // Shouldn't happen, but keep it safe.
                        continue;
                    }

                    // Static part before the placeholder
                    $static = substr($segmentKey, $cursor, $pos - $cursor);
                    if ($static !== '') {
                        $rendered .= rawurlencode($static);
                    }

                    if (!array_key_exists($name, $params)) {
                        throw new InvalidArgumentException("Missing required route param '{$name}' for {$class}.");
                    }

                    $value = (string) $params[$name];
                    $usedParamNames[] = $name;

                    // Validate regex
                    if (@preg_match('/^' . $regex . '$/', '') === false) {
                        throw new InvalidArgumentException("Invalid regex for param '{$name}': /{$regex}/");
                    }

                    // Embedded catch-all cannot contain '/' because it would break the path segment
                    if ($regex === '.+' && str_contains($value, '/')) {
                        throw new InvalidArgumentException("Param '{$name}' must not contain '/' in segment '{$segmentKey}'.");
                    }

                    if (!preg_match('/^' . $regex . '$/', $value)) {
                        throw new InvalidArgumentException("Param '{$name}' value '{$value}' does not match /{$regex}/");
                    }

                    $rendered .= rawurlencode($value);
                    $cursor = $pos + strlen($full);
                }

                // Tail static after the last placeholder
                $tail = substr($segmentKey, $cursor);
                if ($tail !== '') {
                    $rendered .= rawurlencode($tail);
                }

                $builtSegments[] = $rendered;
                continue;
            }

            // Pure placeholder segment like "<slug:.+>" (not embedded)
            if (preg_match('/^<([^:>]+):(.+)>$/', $segmentKey, $m)) {
                $name  = $m[1];
                $regex = $m[2];

                if (!array_key_exists($name, $params)) {
                    throw new InvalidArgumentException("Missing required route param '{$name}' for {$class}.");
                }

                $value = (string) $params[$name];
                $usedParamNames[] = $name;

                if (@preg_match('/^' . $regex . '$/', '') === false) {
                    throw new InvalidArgumentException("Invalid regex for param '{$name}': /{$regex}/");
                }

                if ($regex === '.+') {
                    // catch-all: allow slashes, encode per path segment
                    $parts = array_map('rawurlencode', explode('/', trim($value, '/')));
                    if ($parts === ['']) {
                        throw new InvalidArgumentException("Param '{$name}' must not be empty for {$class}.");
                    }
                    $builtSegments[] = implode('/', $parts);
                } else {
                    if (!preg_match('/^' . $regex . '$/', $value)) {
                        throw new InvalidArgumentException("Param '{$name}' value '{$value}' does not match /{$regex}/");
                    }
                    $builtSegments[] = rawurlencode($value);
                }
                continue;
            }

            // Plain static segment
            $builtSegments[] = rawurlencode($segmentKey);
        }

        // Assemble path
        $path = '/' . ltrim(implode('/', array_filter($builtSegments, fn ($s) => $s !== '')), '/');
        if ($path === '') {
            $path = '/';
        }

        // Unused params → query string
        $unused = array_diff_key($params, array_flip($usedParamNames));
        if (!empty($unused)) {
            $qs = http_build_query($unused, '', '&', PHP_QUERY_RFC3986);
            if ($qs !== '') {
                $path .= (str_contains($path, '?') ? '&' : '?') . $qs;
            }
        }

        return $path;
    }

    /**
     * Optional forward-matcher (kept public if you use it elsewhere).
     * Returns [node, params] or false if not found.
     */
    public function findRouteNode(string $url, array $trie): false|array
    {
        $segments = explode('/', trim($url, '/'));
        $hasWildCardMatch = false;
        $mustMatchFirst = false;
        $node = $trie;
        $params = [];

        // if there is only 1 segment and it is empty, then it is the root
        if (count($segments) === 1 && ($segments[0] === '' || $segments[0] === null)) {
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
                    [$name, $regex] = explode(':', $pattern, 2);
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

            // it's the last index and we are at the end
            // if the segment is index and we have a page, then we are good
            // it's an index page which equals "" path.
            if ($segment === 'index' && $node) {
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

    private function getRoutesFile(): string
    {
        return $this->config->getBuildOutputFolder() . DIRECTORY_SEPARATOR . 'routes.php';
    }

    /**
     * DFS: return the stack of segment keys that lead to a node whose methods match $class.
     *
     * @param array  $node    Current trie node
     * @param string $class   Target class name
     * @param string $method  HTTP method (e.g., GET), '*' in trie also matches
     * @param array  $stack   Accumulated segment keys
     *
     * @return array<string>|null
     */
    private function findPathForClass(array $node, string $class, string $method, array $stack): ?array
    {
        // Does this node have a methods leaf matching the class?
        if (isset($node['_children']['methods']) && is_array($node['_children']['methods'])) {
            $methods = $node['_children']['methods'];
            if (
                (isset($methods[$method]) && ($methods[$method]['class'] ?? null) === $class) ||
                (isset($methods['*']) && ($methods['*']['class'] ?? null) === $class)
            ) {
                return $stack;
            }
        }

        // Recurse into children segments
        foreach ($node as $key => $child) {
            if ($key === '_children') {
                continue;
            }
            if (!is_array($child) || !isset($child['_children'])) {
                continue;
            }
            $found = $this->findPathForClass($child, $class, $method, array_merge($stack, [$key]));
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }
}
