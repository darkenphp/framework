# LogService Usage Example

This example demonstrates how to use the PSR-3 compatible LogService in the DarkenPHP framework.

## Basic Usage

```php
<?php

use Darken\Web\Application;

// Assuming you have a config implementing ConfigInterface
$config = new YourConfig();
$app = new Application($config);

// Get the logger service from the kernel
$logger = $app->getLogService();

// Use all PSR-3 log levels
$logger->emergency('System is down!');
$logger->alert('Database connection lost');
$logger->critical('Critical error occurred');
$logger->error('An error happened', ['user_id' => 123]);
$logger->warning('This is a warning');
$logger->notice('Notable event occurred');
$logger->info('Information message');
$logger->debug('Debug information', ['request_id' => 'abc123']);
```

## Retrieving Logs

```php
// Get all logs
$allLogs = $logger->getLogs();

// Get logs by specific level
$errorLogs = $logger->getLogsByLevel('error');
$infoLogs = $logger->getLogsByLevel('info');

// Clear all logs
$logger->clearLogs();
```

## Using with Dependency Injection

The LogService is automatically registered in the container and can be resolved:

```php
// Get container from kernel
$container = $app->getContainerService();

// Resolve logger directly from container
$logger = $container->resolve(\Darken\Service\LogService::class);
```

## Configuration through LogServiceInterface

You can customize the LogService behavior by implementing LogServiceInterface in your config:

```php
<?php

use Darken\Config\BaseConfig;
use Darken\Service\LogService;
use Darken\Service\LogServiceInterface;

class MyConfig extends BaseConfig implements LogServiceInterface
{
    public function logs(LogService $service): LogService
    {
        // Custom configuration can be added here
        // For example, you could add custom handlers, formatters, etc.
        
        return $service;
    }
    
    // ... other config methods
}
```

## Integration with Debug Bar

The LogService is designed to work with debug bars and logging tools:

```php
// A debug bar could retrieve all logs for display
$debugBar = new SomeDebugBar();
$logs = $app->getLogService()->getLogs();
$debugBar->addLogs($logs);
```

## Log Structure

Each log entry contains:

```php
[
    'level' => 'info',           // The log level
    'message' => 'Log message',  // The log message
    'context' => [],             // Additional context data
    'timestamp' => 1234567890.123 // Microtime timestamp
]
```

This LogService implementation follows PSR-3 standards and integrates seamlessly with the DarkenPHP framework's service container pattern.