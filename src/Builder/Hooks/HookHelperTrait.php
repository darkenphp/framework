<?php

declare(strict_types=1);

namespace Darken\Builder\Hooks;

use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;

trait HookHelperTrait
{
    public function createParam(string $paramName, string $type, mixed $default = null): Param
    {
        $factory = new BuilderFactory();

        // 1) Build the param
        $param = $factory->param($paramName);

        // 2) If there's a type, set it
        $param->setType($type);

        // 3) If there's a default value, set it
        if ($default !== null) {
            // Here you likely want to handle strings, arrays, etc. properly,
            // but for simplicity we do:
            $param->setDefault($default);
        }

        return $param;

    }
}
