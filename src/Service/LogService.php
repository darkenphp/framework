<?php

declare(strict_types=1);

namespace Darken\Service;

use Psr\Log\AbstractLogger;

/**
 * PSR-3 compatible logging service for the DarkenPHP framework.
 * 
 * This service provides standard logging capabilities following PSR-3 standards
 * and integrates seamlessly with the framework's dependency injection container.
 * 
 * Features:
 * - Full PSR-3 compliance with all log levels (emergency, alert, critical, error, warning, notice, info, debug)
 * - Message interpolation with placeholder support (e.g., "User {username} logged in")
 * - Context data support for structured logging
 * - Log retrieval and filtering capabilities
 * - Integration with debug bars and development tools
 * 
 * Usage Examples:
 * 
 * Basic logging:
 * ```php
 * $logger = $kernel->getLogService();
 * $logger->info('User logged in', ['user_id' => 123]);
 * $logger->error('Database connection failed', ['host' => 'localhost']);
 * ```
 * 
 * Message interpolation:
 * ```php
 * $logger->info('User {username} performed {action}', [
 *     'username' => 'john_doe',
 *     'action' => 'login'
 * ]); // Results in: "User john_doe performed login"
 * ```
 * 
 * Retrieving logs:
 * ```php
 * $allLogs = $logger->getLogs();
 * $errorLogs = $logger->getLogsByLevel('error');
 * $logger->clearLogs();
 * ```
 * 
 * Debug bar integration:
 * ```php
 * $debugBar = new SomeDebugBar();
 * $logs = $app->getLogService()->getLogs();
 * $debugBar->addLogs($logs);
 * ```
 * 
 * @package Darken\Service
 */
final class LogService extends AbstractLogger
{
    /**
     * @var array<array{level: string, message: string, context: array, timestamp: float}>
     */
    private array $logs = [];

    /**
     * Constructor.
     * 
     * @param ContainerService $containerService The container service for dependency injection
     */
    public function __construct(private ContainerService $containerService)
    {
    }

    /**
     * Get all stored logs.
     * 
     * Returns an array of all log entries with their level, message, context, and timestamp.
     * Useful for debugging, testing, and integration with debug tools.
     *
     * @return array<array{level: string, message: string, context: array, timestamp: float}>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Get logs filtered by specific level.
     * 
     * Filters and returns only log entries that match the specified level.
     * Useful for analyzing specific types of log entries.
     *
     * @param string $level The log level to filter by (e.g., 'error', 'info', 'debug')
     * @return array<array{level: string, message: string, context: array, timestamp: float}>
     */
    public function getLogsByLevel(string $level): array
    {
        return array_filter($this->logs, fn($log) => $log['level'] === $level);
    }

    /**
     * Clear all stored logs.
     * 
     * Removes all log entries from memory. Useful for testing or when you want
     * to start fresh log collection.
     */
    public function clearLogs(): void
    {
        $this->logs = [];
    }

    /**
     * Logs with an arbitrary level.
     * 
     * This is the core logging method that all PSR-3 log level methods delegate to.
     * Implements message interpolation according to PSR-3 specifications.
     * 
     * Message placeholders are replaced with values from the context array.
     * Placeholder format: {key} where 'key' corresponds to a context array key.
     * 
     * @param mixed $level The log level 
     * @param string|\Stringable $message The log message with optional placeholders
     * @param array $context Additional context data for the log entry
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $interpolatedMessage = $this->interpolate((string) $message, $context);
        
        $this->logs[] = [
            'level' => (string) $level,
            'message' => $interpolatedMessage,
            'context' => $context,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Interpolates context values into message placeholders.
     * 
     * Implements PSR-3 message interpolation specification:
     * - Placeholders are delimited by single braces: {placeholder}
     * - Placeholder names correspond to context array keys
     * - Values must be castable to string (no arrays or non-stringable objects)
     * - Non-matching placeholders are left unchanged
     * 
     * @param string $message The message template with placeholders
     * @param array $context The context array with replacement values
     * @return string The interpolated message
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        
        foreach ($context as $key => $val) {
            // Check that the value can be cast to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = (string) $val;
            }
        }

        // Interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}