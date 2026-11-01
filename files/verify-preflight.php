<?php
declare(strict_types=1);
// Offline check of the auditor's preflight: no model, no keys. Usage: php verify-preflight.php <lab>
// Builds a throwaway project in the system temp folder: this lesson's auditor.php, Target.php and fixtures (with
// their synthetic git history), the lab's vendor/ for the autoloader. Plain checks, no assert methods.
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/Target.php';
use App\Target;
$tmp = sys_get_temp_dir() . '/verify-preflight-' . getmypid();
$run = static function (string $command): string {
    exec($command . ' 2>&1', $lines, $code);
    return $code . "\n" . implode("\n", $lines);
};
$run('rm -rf ' . escapeshellarg($tmp));
mkdir($tmp . '/src', 0o755, true);
copy(__DIR__ . '/auditor.php', $tmp . '/auditor.php');
copy(__DIR__ . '/Target.php', $tmp . '/src/Target.php');
symlink($lab . '/vendor', $tmp . '/vendor');
$run('cp -R ' . escapeshellarg(__DIR__ . '/fixtures') . ' ' . escapeshellarg($tmp));
symlink('../outside.txt', $tmp . '/fixtures/scope/inside/escape.txt');
$history = $run('bash ' . escapeshellarg(__DIR__ . '/make-history.sh') . ' ' . escapeshellarg($tmp . '/fixtures'));
if (!str_contains($history, 'HISTORY READY')) {
    throw new RuntimeException("history: {$history}");
}
$label = Target::fromArgument($tmp . '/fixtures/library')->label;
if ($label !== 'library') {
    throw new RuntimeException("label {$label}");
}
foreach ([null, '', $tmp . '/fixtures/missing', $tmp . '/auditor.php'] as $bad) {
    try {
        Target::fromArgument($bad);
        throw new LogicException('accepted ' . var_export($bad, true));
    } catch (InvalidArgumentException) {
    }
}
$php = escapeshellarg(PHP_BINARY);
$prepend = '-d auto_prepend_file=' . escapeshellarg($tmp . '/src/Target.php');
$audit = static fn (string $target): string => $run("cd {$tmp} && {$php} {$prepend} auditor.php {$target}");
$expect = [
    'fixtures/library' => ["0\n", 'Target: library', 'Composer manifest: found', 'Locked dependencies: found',
        'Git history: found', 'Preflight complete: library'],
    'fixtures/missing' => ["2\n", 'Error: not a directory: fixtures/missing', 'Usage: php auditor.php <directory>'],
    'fixtures/no-manifest' => ["0\n", 'Composer manifest: unavailable (no composer.json)', 'Git history: found',
        'Preflight complete: no-manifest'],
    'fixtures/no-history' => ["0\n", 'Composer manifest: found', 'Locked dependencies: unavailable (no composer.lock)',
        'Git history: unavailable (no git metadata)', 'Preflight complete: no-history'],
];
foreach ($expect as $target => $lines) {
    $output = $audit($target);
    foreach ($lines as $line) {
        if (!str_contains($output, $line)) {
            throw new RuntimeException("{$target}: missing '{$line}' in:\n{$output}");
        }
    }
}
$log = $run("git -C {$tmp}/fixtures/library log --format=%s");
if (!str_contains($log, 'Trim repeated separators in slugs') || str_contains($log, 'AI auditor')) {
    throw new RuntimeException("library history: {$log}");
}
foreach (['library', 'app', 'legacy', 'no-manifest'] as $name) {
    if (!is_dir("{$tmp}/fixtures/{$name}/.git")) {
        throw new RuntimeException("{$name} has no history");
    }
}
foreach (['no-history', 'invalid-manifest', 'scope'] as $name) {
    if (is_dir("{$tmp}/fixtures/{$name}/.git")) {
        throw new RuntimeException("{$name} must have no history");
    }
}
$contract = json_decode((string) file_get_contents(__DIR__ . '/contract.json'), true, flags: JSON_THROW_ON_ERROR);
if ($contract['rubric']['penalties'] !== ['no_test_configuration' => 25, 'no_ci_workflow' => 10,
        'vulnerable_dependency' => 30, 'abandoned_dependency' => 5]
    || $contract['capabilities']['omitted'] !== ['write_file', 'edit_file', 'delete_file', 'bash']
    || $contract['report']['checks'][2]['status'] !== 'unavailable') {
    throw new RuntimeException('contract.json differs from the lesson');
}
$run('rm -rf ' . escapeshellarg($tmp));
echo 'PREFLIGHT OK' . PHP_EOL;
