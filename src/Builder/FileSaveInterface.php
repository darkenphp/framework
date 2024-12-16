<?php

declare(strict_types=1);

namespace Darken\Builder;

interface FileSaveInterface
{
    public function getBuildOutputFilePath(): string;

    public function getBuildOutputContent(): string;
}
