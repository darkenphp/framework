<?php

declare(strict_types=1);

namespace Darken\Web;

use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class Request extends ServerRequest implements ServerRequestInterface
{
}
