<?php

declare(strict_types=1);

namespace Darken\Service;

/**
 * Interface for managing log services in the application.
 *
 * This interface defines a method for configuring logging behavior
 * and allows for custom log handlers, formatters, or processors to be added.
 *
 * Example usage for configuring logging:
 * ```php
 * public function logs(LogService $service): LogService
 * {
 *     // Custom configuration can be added here
 *     return $service;
 * }
 * ```
 */
interface LogServiceInterface
{
    /**
     * Configure the log service.
     *
     * This method allows for customization of the logging service,
     * such as adding custom handlers, formatters, or processors.
     *
     * @param LogService $service The log service to configure
     * @return LogService The configured log service
     */
    public function logs(LogService $service): LogService;
}