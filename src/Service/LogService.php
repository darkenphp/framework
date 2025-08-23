<?php

declare(strict_types=1);

namespace Darken\Service;

/**
 * PSR-3 compatible logger interface.
 * 
 * This interface is compatible with PSR-3 LoggerInterface but defined locally
 * to avoid dependency issues during development. Once PSR-3 is properly installed,
 * this can be replaced with the official interface.
 */
interface LoggerInterface
{
    /**
     * System is unusable.
     */
    public function emergency(string|\Stringable $message, array $context = []): void;

    /**
     * Action must be taken immediately.
     */
    public function alert(string|\Stringable $message, array $context = []): void;

    /**
     * Critical conditions.
     */
    public function critical(string|\Stringable $message, array $context = []): void;

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    public function error(string|\Stringable $message, array $context = []): void;

    /**
     * Exceptional occurrences that are not errors.
     */
    public function warning(string|\Stringable $message, array $context = []): void;

    /**
     * Normal but significant events.
     */
    public function notice(string|\Stringable $message, array $context = []): void;

    /**
     * Interesting events.
     */
    public function info(string|\Stringable $message, array $context = []): void;

    /**
     * Detailed debug information.
     */
    public function debug(string|\Stringable $message, array $context = []): void;

    /**
     * Logs with an arbitrary level.
     */
    public function log($level, string|\Stringable $message, array $context = []): void;
}

final class LogService implements LoggerInterface
{
    private array $logs = [];

    public function __construct(private ContainerService $containerService)
    {
    }

    /**
     * Get all stored logs.
     *
     * @return array<array{level: string, message: string, context: array, timestamp: float}>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Get logs filtered by level.
     *
     * @return array<array{level: string, message: string, context: array, timestamp: float}>
     */
    public function getLogsByLevel(string $level): array
    {
        return array_filter($this->logs, fn($log) => $log['level'] === $level);
    }

    /**
     * Clear all stored logs.
     */
    public function clearLogs(): void
    {
        $this->logs = [];
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
            'timestamp' => microtime(true),
        ];
    }
}