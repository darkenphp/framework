<?php

declare(strict_types=1);

namespace Darken\Web;

use Darken\Code\Runtime;
use Darken\Repository\Config;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Handler implements RequestHandlerInterface
{
    public function __construct(public Config $config)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $currentUrl = $request->getUri()->getPath();

        $tmp = $this->config->getBuildOutputFolder() . '/routes.php';

        $trie = include($tmp);

        $runtimePage = $this->matchTrie($trie, $currentUrl);

        if (!$runtimePage) {
            return new Response(404, [], 'Not found');
        }

        $content = $runtimePage->render();

        if ($content instanceof ResponseInterface) {
            return $content;
        }

        return new Response(200, [
            'Content-Type' => 'text/html',
        ], $content);
    }

    private function matchTrie($trie, $url): false|Runtime
    {
        $segments = explode('/', trim($url, '/'));

        $node = $trie;
        $params = [];

        // if there is only 1 segement and it is empty, then it is the root
        if (count($segments) === 1 && empty($segments[0])) {
            $segments = ['index'];
        } else {
            $segments[] = 'index';
        }

        foreach ($segments as $segment) {

            // Try exact match first
            if (isset($node[$segment])) {
                $node = $node[$segment]['_children'];
                continue;
            }
            // see if node matches regex
            foreach ($node as $key => $child) {
                // example regex would be <slug:\\w+>
                if (strpos($key, '<') === 0 && strpos($key, '>') === strlen($key) - 1) {
                    $pattern = substr($key, 1, -1);
                    [$name, $regex] = explode(':', $pattern);
                    if (preg_match("/^$regex$/", $segment)) {
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

            // No match
            return false;
        }

        // Return the script if found
        $className = $node['class_name'] ?? null;

        if (!$className) {
            return false;
        }
        /** @var Runtime $object */
        $object = new $className($params);
        $object->setRouteParams($params);
        return $object;
    }
}
