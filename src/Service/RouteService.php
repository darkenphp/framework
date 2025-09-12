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
        $this->loadRoutes();
    }

    /**
     * Build a URL path for a compiled page class.
     *
     * @param string $class  Fully-qualified compiled page class (e.g. Build\pages\blogs\slug\api)
     * @param array<string, mixed>  $params Route params used to fill dynamic segments. Extra params become query string.
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
                $rendered = $this->renderSegmentWithPlaceholders($segmentKey, $matches, $params, $usedParamNames, $class);
                $builtSegments[] = $rendered;
                continue;
            }

            // Pure placeholder segment like "<slug:.+>" (not embedded)
            if (preg_match('/^<([^:>]+):(.+)>$/', $segmentKey, $m)) {
                $builtSegments[] = $this->renderPurePlaceholder($m[1], $m[2], $params, $usedParamNames, $class);
                continue;
            }

            // Plain static segment
            $builtSegments[] = rawurlencode($segmentKey);
        }

        // Assemble path
        $filteredSegments = array_filter($builtSegments, fn ($s) => $s !== '');
        $path = empty($filteredSegments) ? '/' : '/' . implode('/', $filteredSegments);

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
     *
     * @param string $url
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}|false
     */
    public function findRouteNode(string $url): false|array
    {
        $segments = explode('/', trim($url, '/'));
        $hasWildCardMatch = false;
        $mustMatchFirst = false;
        $node = $this->trie;
        $params = [];

        // if there is only 1 segment and it is empty, then it is the root
        if (count($segments) === 1 && $segments[0] === '') {
            $segments = ['index'];
            if (array_key_exists('index', $node) && is_array($node['index']) && isset($node['index']['_children']) && is_array($node['index']['_children'])) {
                /** @var array<string, mixed> $childrenNode */
                $childrenNode = $node['index']['_children'];
                return [$childrenNode, $params];
            }
            $mustMatchFirst = true;
        } else {
            $segments[] = 'index';
        }

        foreach ($segments as $index => $segment) {

            // Try exact match first
            if (isset($node[$segment]) && is_array($node[$segment]) && isset($node[$segment]['_children']) && is_array($node[$segment]['_children'])) {
                $node = $node[$segment]['_children'];
                continue;
            }

            // see if node matches regex
            foreach ($node as $key => $child) {
                if (!is_string($key) || !is_array($child) || !isset($child['_children']) || !is_array($child['_children'])) {
                    continue;
                }

                // Example dynamic route key: <id:[a-zA-Z0-9\-]+> or <slug:.+>
                // Also supports embedded parameters like: <id:[a-zA-Z0-9\-]+>-<token:[a-zA-Z0-9\-]+>
                if (str_starts_with($key, '<') && str_ends_with($key, '>')) {
                    // Check if this is a simple single parameter pattern
                    $pattern = substr($key, 1, -1);
                    $parts = explode(':', $pattern, 2);
                    if (count($parts) === 2 && preg_match_all('/<([^:>]+):([^>]+)>/', $key, $paramMatches) === 1) {
                        // Simple single parameter pattern
                        [$name, $regex] = $parts;

                        if ($regex === '.+') {
                            if (preg_match('#^' . $regex . '$#', $segment)) {
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
                        } elseif (preg_match('#^' . $regex . '$#', $segment)) {
                            $params[$name] = $segment;
                            $node = $child['_children'];
                            continue 2;
                        }
                    } else {
                        // Complex pattern with embedded parameters
                        $extractedParams = [];
                        if ($this->matchEmbeddedParameters($key, $segment, $extractedParams)) {
                            $params = array_merge($params, $extractedParams);
                            $node = $child['_children'];
                            continue 2;
                        }
                    }
                }
            }

            // it's the last index and we are at the end
            // if the segment is index and we have a page, then we are good
            // it's an index page which equals "" path.
            if ($segment === 'index' && !empty($node)) {
                continue;
            }

            if (!$hasWildCardMatch) {
                return false;
            }
        }

        if ($mustMatchFirst && !$hasWildCardMatch) {
            return false;
        }

        /** @var array<string, mixed> $typedNode */
        $typedNode = $node;
        return [$typedNode, $params];
    }

    /**
     * Set the trie directly for testing purposes
     *
     * @param array<string, mixed> $trie
     */
    public function setTrieForTesting(array $trie): void
    {
        $this->trie = $trie;
    }

    private function loadRoutes(): void
    {
        $routesFile = $this->getRoutesFile();

        if (!file_exists($routesFile)) {
            // Routes file doesn't exist yet, keep empty trie
            return;
        }

        if (!is_readable($routesFile)) {
            throw new InvalidArgumentException(sprintf('Routes file "%s" is not readable', $routesFile));
        }

        /** @var array<string, mixed> $trie */
        $trie = include $routesFile;

        // if the file is empty or does not return an array, just keep the empty trie, as this can happen
        // if the build command runs, creates the route file, but there are not routes defined yet.
        if (is_array($trie)) {
            $this->trie = $trie;
        }
    }

    private function getRoutesFile(): string
    {
        return $this->config->getBuildOutputFolder() . DIRECTORY_SEPARATOR . 'routes.php';
    }

    /**
     * DFS: return the stack of segment keys that lead to a node whose methods match $class.
     *
     * @param array<string, mixed>  $node    Current trie node
     * @param string $class   Target class name
     * @param string $method  HTTP method (e.g., GET), '*' in trie also matches
     * @param array<string>  $stack   Accumulated segment keys
     *
     * @return array<string>|null
     */
    private function findPathForClass(array $node, string $class, string $method, array $stack): ?array
    {
        // Does this node have a methods leaf matching the class?
        if (isset($node['_children']) && is_array($node['_children']) && isset($node['_children']['methods']) && is_array($node['_children']['methods'])) {
            $methods = $node['_children']['methods'];
            if (
                (isset($methods[$method]) && is_array($methods[$method]) && ($methods[$method]['class'] ?? null) === $class) ||
                (isset($methods['*']) && is_array($methods['*']) && ($methods['*']['class'] ?? null) === $class)
            ) {
                return $stack;
            }
        }

        // Recurse into children segments
        foreach ($node as $key => $child) {
            if ($key === '_children' || !is_string($key)) {
                continue;
            }
            if (!is_array($child) || !isset($child['_children']) || !is_array($child['_children'])) {
                continue;
            }
            $found = $this->findPathForClass($child, $class, $method, array_merge($stack, [$key]));
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Render a segment with embedded placeholders.
     *
     * @param string $segmentKey
     * @param array<array{0: string, 1: string, 2: string}> $matches
     * @param array<string, mixed> $params
     * @param array<string> &$usedParamNames
     * @param string $class
     * @return string
     */
    private function renderSegmentWithPlaceholders(string $segmentKey, array $matches, array $params, array &$usedParamNames, string $class): string
    {
        $cursor = 0;
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

            $paramValue = $params[$name];
            $value = is_scalar($paramValue) ? (string) $paramValue : '';
            if ($value === '' && $paramValue !== '' && $paramValue !== 0 && $paramValue !== '0') {
                throw new InvalidArgumentException("Param '{$name}' must be a scalar value for {$class}.");
            }
            $usedParamNames[] = $name;

            // Validate regex
            if (@preg_match('#^' . $regex . '$#', '') === false) {
                throw new InvalidArgumentException("Invalid regex for param '{$name}': /{$regex}/");
            }

            // Embedded catch-all cannot contain '/' because it would break the path segment
            if ($regex === '.+' && str_contains($value, '/')) {
                throw new InvalidArgumentException("Param '{$name}' must not contain '/' in segment '{$segmentKey}'.");
            }

            if (!preg_match('#^' . $regex . '$#', $value)) {
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

        return $rendered;
    }

    /**
     * Render a pure placeholder segment.
     *
     * @param string $name
     * @param string $regex
     * @param array<string, mixed> $params
     * @param array<string> &$usedParamNames
     * @param string $class
     * @return string
     */
    private function renderPurePlaceholder(string $name, string $regex, array $params, array &$usedParamNames, string $class): string
    {
        if (!array_key_exists($name, $params)) {
            throw new InvalidArgumentException("Missing required route param '{$name}' for {$class}.");
        }

        $paramValue = $params[$name];
        $value = is_scalar($paramValue) ? (string) $paramValue : '';
        if ($value === '' && $paramValue !== '' && $paramValue !== 0 && $paramValue !== '0') {
            throw new InvalidArgumentException("Param '{$name}' must be a scalar value for {$class}.");
        }
        $usedParamNames[] = $name;

        if (@preg_match('#^' . $regex . '$#', '') === false) {
            throw new InvalidArgumentException("Invalid regex for param '{$name}': /{$regex}/");
        }

        if ($regex === '.+') {
            // catch-all: allow slashes, encode per path segment
            $parts = array_map('rawurlencode', explode('/', trim($value, '/')));
            if ($parts === ['']) {
                throw new InvalidArgumentException("Param '{$name}' must not be empty for {$class}.");
            }
            return implode('/', $parts);
        }
        if (!preg_match('#^' . $regex . '$#', $value)) {
            throw new InvalidArgumentException("Param '{$name}' value '{$value}' does not match /{$regex}/");
        }
        return rawurlencode($value);

    }

    /**
     * Match a segment against a pattern with embedded parameters.
     *
     * @param string $pattern The route pattern like '<id:[a-zA-Z0-9\-]+>-<token:[a-zA-Z0-9\-]+>'
     * @param string $segment The URL segment to match against
     * @param array<string, string> &$extractedParams Output array for extracted parameters
     * @return bool True if the segment matches the pattern
     */
    private function matchEmbeddedParameters(string $pattern, string $segment, array &$extractedParams): bool
    {
        // Find all parameter placeholders in the pattern
        if (!preg_match_all('/<([^:>]+):([^>]+)>/', $pattern, $matches, PREG_SET_ORDER)) {
            return false;
        }

        // Build a regex pattern by replacing each placeholder with its regex
        $regexPattern = preg_quote($pattern, '#');
        $paramNames = [];

        foreach ($matches as $match) {
            [$fullMatch, $name, $regex] = $match;
            $quotedFullMatch = preg_quote($fullMatch, '#');
            $regexPattern = str_replace($quotedFullMatch, "({$regex})", $regexPattern);
            $paramNames[] = $name;
        }

        // Try to match the segment against the constructed regex
        if (preg_match("#^{$regexPattern}$#", $segment, $segmentMatches)) {
            // Extract parameter values (skip the full match at index 0)
            for ($i = 1; $i < count($segmentMatches); $i++) {
                $extractedParams[$paramNames[$i - 1]] = $segmentMatches[$i];
            }
            return true;
        }

        return false;
    }
}
