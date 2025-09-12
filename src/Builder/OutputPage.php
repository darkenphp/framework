<?php

declare(strict_types=1);

namespace Darken\Builder;

use Darken\Config\PagesConfigInterface;

class OutputPage
{
    private array|null $nodeData = null;

    // use config and input instead
    public function __construct(public OutputPolyfill $polyfill, public PagesConfigInterface $pagesConfig)
    {

    }

    public function getNodeData(): array
    {
        if ($this->nodeData === null) {
            $extra = $this->polyfill->compilerOutput->data->onPageDataHook($this);
            $extra['class'] = $this->polyfill->getFullQualifiedClassName();
            $this->nodeData = $extra;
        }

        return $this->nodeData;
    }

    public function getSegmentedTrieRoute(): array
    {
        return explode('/', trim($this->getRoute(), '/')); // Split into segments
    }

    public function getVerbs(): array
    {
        $verbs = [];
        /**
         * Regex explanation:
         *
         * ^                         Start of string
         * (                        Start capturing group #1
         *   (?:                    Start a non-capturing group:
         *       \[[^]]*\]          Match bracketed text like [foo] or [[...slug]] (anything except a ']' inside)
         *       |                  OR
         *       [^.]               Any character that is NOT a dot
         *   )*                     Repeat that group zero or more times
         * )                        End capturing group #1
         * \.                       Match a literal dot outside brackets
         * ([^.]+)                  Capturing group #2: one or more non-dot chars => our "method"
         * \.php                    A literal dot followed by "php"
         * $                        End of string
         * /i                       Case-insensitive
         */
        if (preg_match('/^((?:\[[^]]*\]|[^.])*)\.([^.]+)\.php$/i', $this->polyfill->compiled->input->getFileName(), $matches)) {
            $methodsMatch = $matches[2];
            foreach (explode('|', $methodsMatch) as $verb) {
                $verbs[] = $verb;
            }
        }

        foreach ($this->getNodeData()['methods'] ?? [] as $verb) {
            $verbs[] = $verb;
        }

        if (count($verbs) === 0) {
            $verbs[] = '*';
        }

        return array_map('strtoupper', $verbs);
    }

    private function getRoute(): string
    {
        $source = str_replace($this->pagesConfig->getPagesFolder(), '', $this->polyfill->compiled->input->filePath);

        // if the pattern is [[...xyz]] then then match regex should match anything after the slash
        if (str_contains($source, '[[...')) {
            $pattern = preg_replace('/\[\[\.\.\.(.*?)\]\]/', '<$1:.+>', $source);
        } else {
            // Handle prefixed patterns like [[d:id]], [[w:name]], etc.
            $pattern = preg_replace_callback('/\[\[([^:\]]*):?([^\]]*)\]\]/', function ($matches) {
                $firstPart = $matches[1];
                $secondPart = $matches[2];

                // Check if first part is a valid prefix
                $validPrefixes = ['d', 'w', 's', 'a', 'h'];
                if (in_array($firstPart, $validPrefixes) && !empty($secondPart)) {
                    // It's a prefixed parameter like [[d:id]]
                    $prefix = $firstPart;
                    $name = $secondPart;
                } else {
                    // It's either [[name]] or [[something:else]] where something is not a valid prefix
                    if (empty($secondPart)) {
                        // Simple [[name]] pattern
                        $prefix = '';
                        $name = $firstPart;
                    } else {
                        // [[something:else]] where something is not a valid prefix
                        // Treat the whole thing as the parameter name
                        $prefix = '';
                        $name = $firstPart . ':' . $secondPart;
                    }
                }

                $regex = $this->getRegexForPrefix($prefix);
                return "<{$name}:{$regex}>";
            }, $source);
        }

        // if the file ends with .get.php (but could also be .post.php, .put.php, etc)
        // its basically two dots extract this information and replace .php
        // here is the problem with the <>.
        $pattern = preg_replace('/\.[a-zA-Z0-9_\|]+\.php$/', '.php', $pattern);

        // an easy way to convert /blogs/[[slug]] to a matcahable regex like /blogs/<slug:[\w+]>
        return str_replace('.php', '', $pattern);
    }

    private function getRegexForPrefix(string $prefix): string
    {
        return match ($prefix) {
            'd' => '[0-9]+',
            'w' => '[a-zA-Z0-9]+',
            's' => '[a-zA-Z0-9-]+',  // Fixed: hyphen at end without escape
            'a' => '[a-zA-Z]+',
            'h' => '[a-fA-F0-9]+',
            default => '[a-zA-Z0-9-]+',  // Fixed: hyphen at end without escape
        };
    }
}
