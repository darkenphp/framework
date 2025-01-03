<?php

declare(strict_types=1);

namespace Darken\Code;

use Darken\Web\Response;

/**
 * Interface InvokeResponseInterface
 *
 * This interface is used to define the __invoke method for a class that returns a Response object.
 */
interface InvokeResponseInterface
{
    public function __invoke(): Response;
}
