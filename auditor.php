<?php
declare(strict_types=1);
use App\Target;
require __DIR__ . '/vendor/autoload.php';
try {
    $target = Target::fromArgument($argv[1] ?? null);
} catch (InvalidArgumentException $error) {
    fwrite(STDERR, "Error: {$error->getMessage()}\n");
    fwrite(STDERR, "Usage: php auditor.php <directory>\n");
    exit(2);
}
echo "Target: {$target->label}\n";
$checks = [
    'Composer manifest' => $target->has('composer.json')
        ? 'found' : 'unavailable (no composer.json)',
    'Locked dependencies' => $target->has('composer.lock')
        ? 'found' : 'unavailable (no composer.lock)',
    'Git history' => $target->has('.git')
        ? 'found' : 'unavailable (no git metadata)',
];
foreach ($checks as $check => $status) {
    echo "{$check}: {$status}\n";
}
echo "Preflight complete: {$target->label}\n";
