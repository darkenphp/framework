<?php

declare(strict_types=1);

namespace Darken\Builder;

use Darken\Console\Application;

use function Opis\Closure\serialize;

class ExtensionFile implements FileSaveInterface
{
    public string $className = 'Extension';

    /**
     * @param array<FileBuildProcess> $files
     */
    public function __construct(private array $files, private Application $app)
    {

    }

    public function getBuildOutputFilePath(): string
    {
        return $this->app->config->getBuildOutputFolder() . DIRECTORY_SEPARATOR . $this->className . '.php';
    }

    public function getBuildOutputContent(): string
    {
        $dumpFiles = [];
        foreach ($this->files as $file) {
            $dumpFiles[$file->getPolyfillOutput()->getFullQualifiedClassName()] = $file->getPolyfillOutput()->getRelativeBildOutputFilepath();
        }

        $dump = var_export($dumpFiles, true);
        $eventListeneres = base64_encode(serialize($this->app->getEventService()->getListeners()));
        $middlewares = base64_encode(serialize($this->app->getMiddlewareService()->retrieve()));
        $namespace = $this->app->config->getBuildOutputNamespace();
        $className = $this->className;

        $containers = $this->app->getContainerService()->definitions();

        $constructrorParams = [];
        $constructorStmts = [];
        foreach ($containers as $container) {
            $parts = explode('\\', $container);
            $classNameFromContainerName = end($parts);
            $varName = lcfirst($classNameFromContainerName);
            $fullContainerName = '\\' . $container;
            $constructrorParams[] = $fullContainerName . ' $' . $varName;
            $constructorStmts[] = '$this->registerDefinition(\'' . $container . '\', $'.$varName.');';
        }
        $constructror = 'public function __construct(';
        $constructror .= implode(',', $constructrorParams);
        $constructror .= ') {
            ' . implode("\n", $constructorStmts) . '
        }';
        return <<<PHP
            <?php

            namespace $namespace;

            class $className extends \Darken\Service\Extension
            {
                $constructror

                public function getClassMap(): array
                {
                    return $dump;
                }

                public function getSerializedEvents(): string
                {
                    return '$eventListeneres';
                }

                public function getSerializedMiddlewares(): string
                {
                    return '$middlewares';
                }
            }
            PHP;
    }
}
