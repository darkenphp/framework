<?php

declare(strict_types=1);

namespace Darken\Builder;

// RENAME: CompiledPage

class OutputPage
{
    public function __construct(public OutputPolyfill $polyfill)
    {

    }

    public function getRoute(): string
    {
        $source = str_replace($this->polyfill->compiled->config->getPagesFolder(), '', $this->polyfill->compiled->input->filePath);

        // if the pattern is [[...xyz]] then then match regex should match anything after the slash
        if (str_contains($source, '[[...')) {
            $pattern = preg_replace('/\[\[\.\.\.(.*?)\]\]/', '<$1:.+>', $source);
        } else {
            $pattern = preg_replace('/\[\[(.*?)\]\]/', '<$1:[a-zA-Z0-9\-]+>', $source);
        }


        // an easy way to convert /blogs/[[slug]] to a matcahable regex like /blogs/<slug:[\w+]>
        return str_replace('.php', '', $pattern);
    }

    public function getSegmentedTrieRoute(): array
    {
        return explode('/', trim($this->getRoute(), '/')); // Split into segments
    }
}
