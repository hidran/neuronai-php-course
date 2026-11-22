<?php

declare(strict_types=1);
$lab = $argv[1] ?? dirname(__DIR__);
require $lab . '/vendor/autoload.php';
// CheckPackagesTool.php autoloads from src/Tools/CheckPackagesTool.php

use App\Tools\CheckPackagesTool;

$tool = new CheckPackagesTool();
$out = (string) $tool([
    ['package' => 'vlucas/phpdotenv', 'minimum' => '5.0'],
    ['package' => 'nope/nope'],
]);
if (!str_contains($out, 'vlucas/phpdotenv: v5') || !str_contains($out, 'meets 5.0')) {
    throw new RuntimeException("unexpected: {$out}");
}
if (!str_contains($out, 'nope/nope: not installed')) {
    throw new RuntimeException('missing package not reported');
}
$schema = json_encode($tool->getInputSchema());
if (!str_contains($schema, '"maxItems":5')) {
    throw new RuntimeException("schema: {$schema}");
}
echo 'CHECK PACKAGES OK' . PHP_EOL;
