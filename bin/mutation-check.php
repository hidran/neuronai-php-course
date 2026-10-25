<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$mode = $argv[1] ?? 'repeat';
$phpunit = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(
    $root . '/vendor/phpunit/phpunit/phpunit'
) . ' --configuration ' . escapeshellarg($root . '/phpunit.xml');
if ($mode === 'repeat') {
    $start = hrtime(true);
    for ($run = 1; $run <= 50; $run++) {
        exec($phpunit . ' 2>&1', $output, $status);
        if ($status !== 0) {
            throw new RuntimeException('Repeated suite failed.');
        }
        unset($output);
    }
    printf("Repeated runs: 50 passed in %.3f seconds.\n",
        (hrtime(true) - $start) / 1e9);
    exit(0);
}
$changes = [
    'visibility' => ['SensitiveTool.php',
        '$this->visible($authorized);', '$this->visible(true);',
        'testRestrictedCapabilityIsNotOffered'],
    'retry' => ['ToolErrorPolicy.php',
        'Retry with valid coordinates.', 'Try valid coordinates.',
        'testRecoveryInstructionAndRedaction'],
    'leak' => ['ToolErrorPolicy.php',
        "return 'Weather unavailable.",
        "return \$error->getMessage() . 'Weather unavailable.",
        'testRecoveryInstructionAndRedaction'],
];
if (!isset($changes[$mode])) {
    throw new InvalidArgumentException('Unknown mutation.');
}
[$name, $before, $after, $test] = $changes[$mode];
$file = $root . '/src/Deterministic/' . $name;
$original = file_get_contents($file);
$changed = str_replace($before, $after, $original, $count);
if ($count !== 1) {
    throw new RuntimeException('Mutation target changed.');
}
try {
    file_put_contents($file, $changed);
    passthru($phpunit . ' --filter ' . escapeshellarg($test), $rc);
    if ($rc !== 1) {
        throw new RuntimeException('Expected an assertion failure.');
    }
    echo "Mutation detected: $mode\n";
} finally {
    file_put_contents($file, $original);
}
echo "Original source restored.\n";
