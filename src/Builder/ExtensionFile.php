<?php

declare(strict_types=1);

namespace Darken\Builder;

use Darken\Console\Application;
use Darken\Console\Commands\FileBuildProcess;

use function Opis\Closure\serialize;

class ExtensionFile implements FileSaveInterface
{
    public $className = 'Extension';

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
        $eventListeneres = base64_encode(serialize($this->app->getEventService()->getListeneres()));
        $middlwares = base64_encode(serialize($this->app->getMiddlwareService()->retrieve()));
        $namespace = $this->app->config->getBuildOutputNamespace();
        $className = $this->className;
        return <<<PHP
            <?php

            namespace $namespace;

            class $className extends \Darken\Service\Extension
            {
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
                    return '$middlwares';
                }
            }
            PHP;
    }
}
