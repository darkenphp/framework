<?php

declare(strict_types=1);

namespace Darken\Config;

/**
 * Interface PagesConfigInterface
 *
 * This interface is used to define the method for getting the folder path for pages in the application. It
 * therfore defines the application to make use of pages and routing.
 */
interface PagesConfigInterface
{
    /**
     * Get the folder path for pages in the application.
     *
     * The folder is resolved relative to the root directory and can be customized
     * using the `DARKEN_PAGES_FOLDER` environment variable.
     *
     * Example:
     * ```php
     * return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->env('DARKEN_PAGES_FOLDER', 'pages');
     * ```
     *
     * @return string The pages folder path.
     */
    public function getPagesFolder(): string;
}
