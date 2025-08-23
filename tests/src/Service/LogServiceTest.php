<?php

namespace Tests\src\Service;

use Darken\Service\ContainerService;
use Darken\Service\LogService;
use Tests\TestCase;

class LogServiceTest extends TestCase
{
    public function testLogServiceImplementsLoggerInterface()
    {
        $logService = new LogService();
        
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logService);
    }

    public function testEmergencyLog()
    {
        $logService = new LogService();
        
        $logService->emergency('Emergency message', ['context' => 'test']);
        
        $logs = $logService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals('emergency', $logs[0]['level']);
        $this->assertEquals('Emergency message', $logs[0]['message']);
        $this->assertEquals(['context' => 'test'], $logs[0]['context']);
        $this->assertIsFloat($logs[0]['timestamp']);
    }

    public function testAlertLog()
    {
        $logService = new LogService();
        
        $logService->alert('Alert message');
        
        $logs = $logService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals('alert', $logs[0]['level']);
        $this->assertEquals('Alert message', $logs[0]['message']);
    }

    public function testCriticalLog()
    {
        $logService = new LogService();
        
        $logService->critical('Critical message');
        
        $logs = $logService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals('critical', $logs[0]['level']);
    }

    public function testErrorLog()
    {
        $logService = new LogService();
        
        $logService->error('Error message');
        
        $logs = $logService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals('error', $logs[0]['level']);
    }

    public function testWarningLog()
    {
        $logService = new LogService();
        
        $logService->warning('Warning message');
        
        $logs = $logService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals('warning', $logs[0]['level']);
    }

    public function testNoticeLog()
    {
        $logService = new LogService();
        
        $logService->notice('Notice message');
        
        $logs = $logService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals('notice', $logs[0]['level']);
    }

    public function testInfoLog()
    {
        $logService = new LogService();
        
        $logService->info('Info message');
        
        $logs = $logService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals('info', $logs[0]['level']);
    }

    public function testDebugLog()
    {
        $logService = new LogService();
        
        $logService->debug('Debug message');
        
        $logs = $logService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals('debug', $logs[0]['level']);
    }

    public function testGenericLog()
    {
        $logService = new LogService();
        
        $logService->log('custom', 'Custom message', ['key' => 'value']);
        
        $logs = $logService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals('custom', $logs[0]['level']);
        $this->assertEquals('Custom message', $logs[0]['message']);
        $this->assertEquals(['key' => 'value'], $logs[0]['context']);
    }

    public function testMultipleLogs()
    {
        $logService = new LogService();
        
        $logService->info('First message');
        $logService->error('Second message');
        $logService->debug('Third message');
        
        $logs = $logService->getLogs();
        $this->assertCount(3, $logs);
    }

    public function testGetLogsByLevel()
    {
        $logService = new LogService();
        
        $logService->info('Info message 1');
        $logService->error('Error message');
        $logService->info('Info message 2');
        
        $infoLogs = $logService->getLogsByLevel('info');
        $errorLogs = $logService->getLogsByLevel('error');
        
        $this->assertCount(2, $infoLogs);
        $this->assertCount(1, $errorLogs);
        $this->assertEquals('Info message 1', $infoLogs[0]['message']);
        $this->assertEquals('Info message 2', $infoLogs[2]['message']);
        $this->assertEquals('Error message', $errorLogs[1]['message']);
    }

    public function testClearLogs()
    {
        $logService = new LogService();
        
        $logService->info('Message 1');
        $logService->error('Message 2');
        $this->assertCount(2, $logService->getLogs());
        
        $logService->clearLogs();
        $this->assertCount(0, $logService->getLogs());
    }

    public function testStringableMessage()
    {
        $logService = new LogService();
        
        $stringableMessage = new class implements \Stringable {
            public function __toString(): string {
                return 'Stringable message';
            }
        };
        
        $logService->info($stringableMessage);
        
        $logs = $logService->getLogs();
        $this->assertEquals('Stringable message', $logs[0]['message']);
    }

    public function testMessageInterpolation()
    {
        $logService = new LogService();
        
        $logService->info('User {username} performed {action}', [
            'username' => 'john_doe',
            'action' => 'login',
            'extra' => 'ignored'
        ]);
        
        $logs = $logService->getLogs();
        $this->assertEquals('User john_doe performed login', $logs[0]['message']);
        $this->assertEquals(['username' => 'john_doe', 'action' => 'login', 'extra' => 'ignored'], $logs[0]['context']);
    }

    public function testMessageInterpolationWithInvalidValues()
    {
        $logService = new LogService();
        
        $logService->info('User {username} has {items} items and {data}', [
            'username' => 'john',
            'items' => 5,
            'data' => ['not' => 'interpolated'], // Array should not be interpolated
            'missing' => 'value'
        ]);
        
        $logs = $logService->getLogs();
        // Should interpolate username and items, but leave {data} as-is since it's an array
        $this->assertEquals('User john has 5 items and {data}', $logs[0]['message']);
    }

    public function testMessageInterpolationWithStringableObject()
    {
        $logService = new LogService();
        
        $stringableValue = new class implements \Stringable {
            public function __toString(): string {
                return 'stringable_value';
            }
        };
        
        $logService->info('Object value: {value}', ['value' => $stringableValue]);
        
        $logs = $logService->getLogs();
        $this->assertEquals('Object value: stringable_value', $logs[0]['message']);
    }
}