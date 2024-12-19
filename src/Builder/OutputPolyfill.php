<?php

declare(strict_types=1);

namespace Darken\Builder;

class OutputPolyfill implements FileSaveInterface
{
    public function __construct(public OutputCompiled $compiled, public CodeCompilerOutput $compilerOutput)
    {

    }

    public function getNamespace(): string
    {
        return $this->compiled->config->getBuildOutputNamespace() . str_replace(DIRECTORY_SEPARATOR, '\\', $this->compiled->getRelativeDirectory());
    }

    public function getBuildOutputFilePath(): string
    {
        // replace the .php at the end with .polyfill.php
        return str_replace('.compiled.php', '.php', $this->compiled->getBuildOutputFilePath());
    }

    public function getRelativeBuildOutputFilePath(): string
    {
        return str_replace('.php', '.compiled.php', $this->compiled->input->getFileName());
    }

    public function getFullQualifiedClassName(): string
    {
        return $this->getNamespace() . '\\' . $this->getClassName();
    }

    public function getClassName(): string
    {
        $class = pathinfo($this->compiled->input->filePath, PATHINFO_FILENAME);

        // ensure only a-z0-9 are allowed
        return preg_replace('/[^a-zA-Z0-9]/', '', $class);
    }

    public function getConstructorMethod(): string
    {
        $constructor = $this->compilerOutput->getMeta('constructor');

        if (count($constructor) === 0) {
            return '';
        }

        $params = [];
        $assignments = [];

        foreach ($constructor as $param) {
            // Extract parameter details
            $paramType = $param['paramType'] ?? 'mixed';
            $paramName = $param['paramName'] ?? 'param';
            $propertyName = $param['propertyName'] ?? $paramName;

            // Build the parameter string with type hint
            $params[] = "{$paramType} \${$paramName}";

            // Build the assignment string
            $assignments[] = "\$this->setArgumentParam(\"{$propertyName}\", \${$paramName});";
        }

        // Join parameters and assignments into strings
        $paramsString = implode(', ', $params);
        $assignmentsString = implode("\n        ", $assignments);

        // Construct the full constructor method
        return <<<EOT
            public function __construct({$paramsString})
                {
                    {$assignmentsString}
                }

            EOT;
    }

    public function getSlotMethods(): string
    {
        $slots = $this->compilerOutput->getMeta('slots');

        if (count($slots) === 0) {
            return '';
        }

        $slotMethods = [];
        foreach ($slots as $slot) {
            $name = $slot['paramName'];
            $startTag = 'open'.ucfirst($name);
            $closetag = 'close'.ucfirst($name);

            $slotMethods[] = <<<EOT
                    public function {$startTag}() : self
                    {
                        ob_start();
                        return \$this;
                    }

                    public function {$closetag}() : self
                    {
                        \$this->setSlot('{$name}', ob_get_clean());
                        return \$this;
                    }

                EOT;
        }

        return implode("\n", $slotMethods);
    }

    public function getBuildOutputContent(): string
    {
        return <<<PHP
            <?php
            namespace {$this->getNamespace()};

            class {$this->getClassName()} extends \Darken\Code\Runtime
            {
                {$this->getConstructorMethod()}
                {$this->getSlotMethods()}
                public function renderFilePath(): string
                {
                    return dirname(__FILE__) . DIRECTORY_SEPARATOR  . '{$this->getRelativeBuildOutputFilePath()}';
                }
            }
            PHP;
    }
}
