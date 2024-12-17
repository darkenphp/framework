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
        // an easy way to convert /blogs/[[slug]] to a matcahable regex like /blogs/<slug:[\w+]>
        return str_replace('.php', '', preg_replace('/\[\[(.*?)\]\]/', '<$1:\w+>', $source));
    }

    public function getSegmentedTrieRoute(): array
    {
        return explode('/', trim($this->getRoute(), '/')); // Split into segments
    }
}
