<?php
$commandFile = '/app/scripts/commands.txt';

// Read and preprocess commands
$commands = file($commandFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($commands === false) {
    die("Error reading command file: $commandFile");
}

// Process commands with proper background execution handling
$processedCommands = array_map(function ($cmd) {
    $cmd = trim($cmd);

//    // Handle background commands properly
//    if (str_ends_with($cmd, '&')) {
//        return '(' . rtrim($cmd, '&') . ') &'; // Wrap in subshell
//    }

    return $cmd;
}, $commands);

// Build safe command string
$commandChain = implode(' && ', array_map(function ($cmd) {
    return $cmd;
//    return escapeshellcmd($cmd);
}, $processedCommands));

// Add explicit wait for background jobs
$fullCommand = "bash -c '{$commandChain}; wait'";

// Execute with real-time output
echo "Executing in Bash context:\n";
echo "==========================\n";

echo $fullCommand;
passthru($fullCommand, $returnCode);

// Display results
echo "\n==========================\n";
echo "Exit Code: $returnCode\n";

// Error analysis
if ($returnCode !== 0) {
    echo "\nDebugging Information:\n";
    echo "Processed command chain:\n";
    highlight_string("<?php\n" . implode("\n", $processedCommands) . "\n?>");

    // Suggest common fixes
    echo "\nCommon issues:\n";
    echo "- Remove space before &\n";
    echo "- Use ';' instead of '&&' after background commands\n";
    echo "- Wrap background commands in subshells: '(command &)'\n";
}