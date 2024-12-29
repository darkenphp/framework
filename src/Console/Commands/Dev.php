<?php

declare(strict_types=1);

namespace Darken\Console\Commands;

use Darken\Console\Application;
use Darken\Console\CommandInterface;

class Dev implements CommandInterface
{
    private $processes = [];

    public function run(Application $app): void
    {
        $app->stdOut('Build code');
        $build = new Build();
        $build->run($app);

        $port = $app->getArgument('port', 8009);

        $app->stdOut('Starting development server on http://localhost:'.$port);


        // Define the commands
        $webServerCmd = ['php', '-S', 'localhost:'.$port, '-t', 'public'];
        // Uncomment the following line to add the watch command
        $watchCommand = ['php', 'darken', 'watch'];

        // Start the Web Server process
        $this->processes[] = $this->startProcess($webServerCmd, 'Web Server');

        // Start the Watcher process (if needed)
        $this->processes[] = $this->startProcess($watchCommand, 'Watcher');

        // Handle SIGINT (Ctrl+C) for graceful shutdown
        if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function () {
                $this->terminateProcesses();
                exit(0);
            });
        } else {
            $app->stdOut("Warning: PCNTL functions are not available. Graceful shutdown is not possible.\n");
        }

        // Main loop to monitor processes and handle output
        while (true) {
            foreach ($this->processes as $key => $proc) {
                $status = proc_get_status($proc['process']);

                if (!$status['running']) {
                    // Process has exited unexpectedly
                    $exitCode = $status['exitcode'];
                    $app->stdOut("[{$proc['name']}] Process has terminated with exit code {$exitCode}.\n");

                    // Terminate all processes and exit
                    $this->terminateProcesses();
                    exit($exitCode);
                }

                // Read and display output
                $this->readOutput($proc['pipes'], $proc['name'], $app);
            }

            // Sleep briefly to prevent high CPU usage
            usleep(100000); // 0.1 seconds
        }
    }

    /**
     * Reads output from the given pipes and echoes it to the console.
     *
     * @param array  $pipes Array containing the pipe resources.
     * @param string $name  Name of the process for logging purposes.
     */
    private function readOutput($pipes, string $name, Application $app)
    {
        $read = [];
        if (isset($pipes[1]) && is_resource($pipes[1])) { // stdout
            $read[] = $pipes[1];
        }
        if (isset($pipes[2]) && is_resource($pipes[2])) { // stderr
            $read[] = $pipes[2];
        }

        if (empty($read)) {
            return;
        }

        $write = null;
        $except = null;
        $ready = stream_select($read, $write, $except, 0, 200000); // 0 sec, 200ms

        if ($ready === false) {
            // Error in stream_select
            return;
        }

        foreach ($read as $r) {
            $data = fread($r, 8192);
            if ($data === false || strlen($data) === 0) {
                continue;
            }

            echo date('H:i:s') . ' ';
            echo $app->stdTextYellow("[{$name}]");
            echo ' ' . $data;
        }
    }

    /**
     * Terminates all running processes and closes their pipes.
     */
    private function terminateProcesses()
    {
        foreach ($this->processes as $proc) {
            $status = proc_get_status($proc['process']);
            if ($status['running']) {
                proc_terminate($proc['process']);
                echo "Terminated {$proc['name']} process.\n";
            }

            // Close all pipes except stdin (already closed)
            foreach ($proc['pipes'] as $key => $pipe) {
                if ($key === 0) { // stdin, already closed
                    continue;
                }
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }

            // Close the process resource
            proc_close($proc['process']);
        }

        // Clear the processes array to prevent further operations
        $this->processes = [];
    }

    /**
     * Starts a subprocess with the given command and name.
     *
     * @param array  $command The command to execute as an array.
     * @param string $name    The name of the process for logging purposes.
     *
     * @return array An associative array containing the process resource, pipes, and name.
     */
    private function startProcess(array $command, string $name): array
    {
        // Create the command string with proper escaping
        $cmdStr = implode(' ', array_map('escapeshellarg', $command));

        // Define the descriptor specifications for proc_open
        $descriptorspec = [
            0 => ['pipe', 'r'],   // stdin is a pipe that the child will read from
            1 => ['pipe', 'w'],   // stdout is a pipe that the child will write to
            2 => ['pipe', 'w'],   // stderr is a pipe that the child will write to
        ];

        // Start the process
        $process = proc_open($cmdStr, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            fwrite(STDERR, "Failed to start {$name} process.\n");
            exit(1);
        }

        // Set the stdout and stderr pipes to non-blocking mode
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        // Close the stdin pipe since we are not using it
        fclose($pipes[0]);

        return [
            'process' => $process,
            'pipes'   => $pipes,
            'name'    => $name,
        ];
    }
}
