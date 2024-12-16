<?php

declare(strict_types=1);

namespace Darken\Web;

use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ResponseInterface;

class Response extends Psr7Response implements ResponseInterface
{
}
