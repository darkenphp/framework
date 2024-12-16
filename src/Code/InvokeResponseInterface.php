<?php

declare(strict_types=1);

namespace Darken\Code;

use Darken\Web\Response;

interface InvokeResponseInterface
{
    public function __invoke(): Response;
}
