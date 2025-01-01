<?php

declare(strict_types=1);

namespace Darken\Config;

abstract class BaseConfig implements ConfigInterface, PagesConfigInterface
{
    use ConfigHelperTrait;
}
