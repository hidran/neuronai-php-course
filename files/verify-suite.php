<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
$phpunit = $lab . '/vendor/phpunit/phpunit/phpunit';
if (!is_file($phpunit)) {
    echo 'SUITE SKIPPED (no phpunit in the lab)' . PHP_EOL;
    exit(0);
}
$src = $lab . '/src/Deterministic';
$tests = $lab . '/tests-verify';
@mkdir($src, recursive: true);
@mkdir($tests, recursive: true);
$classes = ['WeatherClient', 'StubWeatherClient', 'HttpWeatherClient',
    'WeatherTool', 'ToolErrorPolicy', 'RecoveryAgent', 'SensitiveService',
    'SensitiveTool', 'InvoiceLine', 'Invoice', 'InvoiceConsistency'];
$suites = ['WeatherTest', 'InvoiceTest', 'RecoveryTest', 'VisibilityTest',
    'OrchestrationTest', 'ExerciseTest'];
try {
    foreach ($classes as $class) {
        copy(__DIR__ . "/{$class}.php", "{$src}/{$class}.php");
    }
    foreach ($suites as $suite) {
        copy(__DIR__ . "/{$suite}.php", "{$tests}/{$suite}.php");
    }
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($phpunit)
        . ' --bootstrap ' . escapeshellarg($lab . '/vendor/autoload.php')
        . ' --no-configuration ' . escapeshellarg($tests) . ' 2>&1';
    exec($command, $output, $status);
    $text = implode(PHP_EOL, $output);
    if ($status !== 0 || !preg_match('/OK \((\d+) tests, (\d+) assertions\)/',
        $text, $m)) {
        throw new RuntimeException("phpunit failed:\n{$text}");
    }
    if ((int) $m[1] < 14) {
        throw new RuntimeException("only {$m[1]} tests ran");
    }
} finally {
    foreach ($classes as $class) {
        @unlink("{$src}/{$class}.php");
    }
    foreach ($suites as $suite) {
        @unlink("{$tests}/{$suite}.php");
    }
    @rmdir($src);
    @rmdir($tests);
}
echo "SUITE OK ({$m[1]} tests, {$m[2]} assertions)" . PHP_EOL;
