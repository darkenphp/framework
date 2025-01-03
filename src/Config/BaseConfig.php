<?php

declare(strict_types=1);

namespace Darken\Config;

/**
 * Class BaseConfig
 *
 * This class is used to define the base configuration for the application. Its
 * a helper class that implements the ConfigInterface and PagesConfigInterface and
 * provides the necessary methods to access the configuration via the ConfigHelperTrait.
 */
abstract class BaseConfig implements ConfigInterface, PagesConfigInterface
{
    use ConfigHelperTrait;
}
