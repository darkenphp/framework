<?php

declare(strict_types=1);

namespace Darken\Config;

/**
 * Interface for managing configuration settings in the application.
 * 
 * This interface provides methods to retrieve essential configuration values such as
 * root directory paths, debug modes, and folders used during the build process.
 * 
 * To simplify its implementation, you can use the provided `ConfigHelperTrait`.
 * Example usage:
 * 
 * ```php
 * use Darken\Config\ConfigHelperTrait;
 * 
 * public function __construct(private readonly string $rootDirectoryPath)
 * {
 *     $this->loadEnvFile();
 * }
 * ```
 */
interface ConfigInterface
{
    /**
     * Get the root directory path of the application.
     * 
     * This path serves as the base for resolving other directories and configuration values.
     * 
     * Example:
     * ```php
     * return $this->path($this->rootDirectoryPath);
     * ```
     * 
     * @return string The root directory path.
     */
    public function getRootDirectoryPath(): string;

    /**
     * Determine if the application is in debug mode.
     * 
     * The debug mode can be enabled or disabled via the `DARKEN_DEBUG` environment variable.
     * 
     * Example:
     * ```php
     * return (bool) $this->env('DARKEN_DEBUG', false);
     * ```
     * 
     * @return bool True if debug mode is enabled; otherwise, false.
     */
    public function getDebugMode(): bool;

    /**
     * Get the output folder path for build artifacts.
     * 
     * The folder is resolved relative to the root directory and can be customized
     * using the `DARKEN_BUILD_OUTPUT_FOLDER` environment variable.
     * 
     * Example:
     * ```php
     * return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->env('DARKEN_BUILD_OUTPUT_FOLDER', '.build');
     * ```
     * 
     * @return string The build output folder path.
     */
    public function getBuildOutputFolder(): string;

    /**
     * Get the namespace used for build output.
     * 
     * The namespace can be customized using the `DARKEN_BUILD_OUTPUT_NAMESPACE` environment variable.
     * 
     * Example:
     * ```php
     * return $this->env('DARKEN_BUILD_OUTPUT_NAMESPACE', 'Build');
     * ```
     * 
     * @return string The build output namespace.
     */
    public function getBuildOutputNamespace(): string;

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

    /**
     * Get a list of folders involved in the building process.
     * 
     * This typically includes the components folder and the pages folder.
     * 
     * Example:
     * ```php
     * return [
     *     $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . 'components',
     *     $this->getPagesFolder(),
     * ];
     * ```
     * 
     * @return string[] An array of folder paths used in the build process.
     */
    public function getBuildingFolders(): array;
}
