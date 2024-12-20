<?php

declare(strict_types=1);

namespace Darken\Web;

use Darken\Code\Runtime;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class PageHandler implements RequestHandlerInterface
{
    private array $trie = [];

    private false|array $node;

    public function __construct(public Application $app, string $path)
    {
        $routesFile = $this->app->config->getBuildOutputFolder() . '/routes.php';

        if (file_exists($routesFile) && !is_readable($routesFile)) {
            throw new RuntimeException('Routes file is not readable');
        }

        if (file_exists($routesFile)) {
            $this->trie = include($routesFile);
        }

        $this->node = $this->findRouteNode($path, $this->trie);
    }

    public function getMiddlewares(): array
    {
        return $this->node[0]['middlewares'] ?? [];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $node = $this->node;

        if (!$node) {
            return new Response(404, [], 'Page not found');
        }

        $runtimePage = $this->createRuntime($node[0], $node[1]);

        if ($runtimePage === false) {
            return new Response(404, [], 'Page Runtime not found');
        }

        $content = $runtimePage->render();

        if ($content instanceof ResponseInterface) {
            return $content;
        }

        return new Response(200, [
            'Content-Type' => 'text/html',
        ], $content);
    }

    private function createRuntime(array $node, array $params): false|Runtime
    {
        // Return the script if found
        $className = $node['class'] ?? null;

        if (!$className) {
            return false;
        }

        /** @var Runtime $object */
        $object = $this->app->getContainerService()->createObject($className, $params);
        $object->setRouteParams($params);
        return $object;

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
