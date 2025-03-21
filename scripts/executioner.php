<?php

function executeCommandsInContext($filePath)
{
    // Read commands from file
    $commands = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($commands === false) {
        throw new Exception("Failed to read file: $filePath");
    }

    // Create unique prompt string
    $prompt = "CMD_PROMPT_" . uniqid() . "_";

    // Configure process pipes
    $descriptors = [
        0 => ["pipe", "r"],  // stdin
        1 => ["pipe", "w"],  // stdout
        2 => ["pipe", "w"]   // stderr
    ];

    // Start interactive bash shell with custom prompt
    $process = proc_open(
        ['bash', '--norc', '-i',],  // Command and arguments as array
        $descriptors,
        $pipes
    );

    if (!is_resource($process)) {
        throw new Exception("Failed to start bash process");
    }

    // Set non-blocking mode for output streams
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    // Set custom prompt
    fwrite($pipes[0], "export PS1='$prompt'\n");
    fflush($pipes[0]);

    // Wait for initial prompt
    readUntilPrompt($pipes, $prompt);

    $results = [];
    foreach ($commands as $command) {
        // Skip empty commands
        if (trim($command) === '') continue;

        // Send command
        fwrite($pipes[0], $command . "\n");
        fflush($pipes[0]);

        // Read output until we see the prompt
        $output = readUntilPrompt($pipes, $prompt);

        $results[] = [
            'command' => $command,
            'output' => $output['stdout'],
            'error' => $output['stderr']
        ];
    }

    // Cleanup
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return $results;
}

function readUntilPrompt($pipes, $prompt)
{
    $stdout = '';
    $stderr = '';
    $buffer = '';
    $timeout = 5; // seconds

    do {
        // Check if prompt exists in buffer
        $pos = strpos($buffer, $prompt);
        if ($pos !== false) {
            $stdout = substr($buffer, 0, $pos);
            $buffer = substr($buffer, $pos + strlen($prompt));
            break;
        }

        // Use stream_select to wait for output
        $read = [$pipes[1], $pipes[2]];
        $write = null;
        $except = null;
        $changed = stream_select($read, $write, $except, $timeout);

        if ($changed === false) {
            break; // Error occurred
        } elseif ($changed > 0) {
            foreach ($read as $stream) {
                $chunk = fread($stream, 4096);
                echo $chunk;
                if ($chunk === false || $chunk === '') continue;

                if ($stream === $pipes[1]) {
                    $buffer .= $chunk;
                    $stdout .= $chunk;
                } elseif ($stream === $pipes[2]) {
                    $stderr .= $chunk;
                }
            }
        } else {
            // Timeout occurred
            break;
        }
    } while (true);

    return ['stdout' => $stdout, 'stderr' => $stderr];
}

try {
    $results = executeCommandsInContext("/app/scripts/commands.txt");
    foreach ($results as $result) {
        echo "Command: " . $result['command'] . "\n";
        echo "Output: " . $result['output'] . "\n";
        echo "Error: " . $result['error'] . "\n\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}