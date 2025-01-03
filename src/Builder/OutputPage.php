<?php

declare(strict_types=1);

namespace Darken\Builder;

use Darken\Config\PagesConfigInterface;

class OutputPage
{
    // use config and input instead
    public function __construct(public OutputPolyfill $polyfill, public PagesConfigInterface $pagesConfig)
    {

    }

    public function getNodeData(): array
    {
        $extra = $this->polyfill->compilerOutput->data->onPageDataHook($this);
        $extra['class'] = $this->polyfill->getFullQualifiedClassName();
        return $extra;
    }

    public function getSegmentedTrieRoute(): array
    {
        return explode('/', trim($this->getRoute(), '/')); // Split into segments
    }

    private function getRoute(): string
    {
        $source = str_replace($this->pagesConfig->getPagesFolder(), '', $this->polyfill->compiled->input->filePath);

        // if the pattern is [[...xyz]] then then match regex should match anything after the slash
        if (str_contains($source, '[[...')) {
            $pattern = preg_replace('/\[\[\.\.\.(.*?)\]\]/', '<$1:.+>', $source);
        } else {
            $pattern = preg_replace('/\[\[(.*?)\]\]/', '<$1:[a-zA-Z0-9\-]+>', $source);
        }

        // an easy way to convert /blogs/[[slug]] to a matcahable regex like /blogs/<slug:[\w+]>
        return str_replace('.php', '', $pattern);
    }
}
