<?php

declare(strict_types=1);

namespace Darken\Config;

/**
 * The config interface, you can implement helpers to make this easier to use.
 *
 * use Darken\Config\ConfigHelperTrait;
 *
 * public function __construct(private readonly string $rootDirectoryPath)
 * {
 *     $this->loadEnvFile();
 * }
 */
interface ConfigInterface
{
    /**
     * ```
     * return $this->path($this->rootDirectoryPath);
     * ```
     */
    public function getRootDirectoryPath(): string;

    /**
     * ```
     * return (bool) $this->env('DARKEN_DEBUG', false);
     * ```
     */
    public function getDebugMode(): bool;

    /**
     * ```
     * return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->env('DARKEN_BUILD_OUTPUT_FOLDER', '.build');
     * ```
     */
    public function getBuildOutputFolder(): string;

    /**
     * ```
     * return $this->env('DARKEN_BUILD_OUTPUT_NAMESPACE', 'Build');
     * ```
     */
    public function getBuildOutputNamespace(): string;

    /**
     * ```
     * return $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . $this->env('DARKEN_PAGES_FOLDER', 'pages');
     * ```
     */
    public function getPagesFolder(): string;

    /**
     * ```
     * return [
     *     $this->getRootDirectoryPath() . DIRECTORY_SEPARATOR . 'components',
     *     $this->getPagesFolder(),
     * ];
     * ```
     */
    public function getBuildingFolders(): array;
}
