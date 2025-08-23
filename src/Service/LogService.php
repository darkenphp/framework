<?php

declare(strict_types=1);

namespace Darken\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
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