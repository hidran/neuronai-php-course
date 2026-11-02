<?php
// Exercise solution: read a candidate package's manifest before adopting it.
// Run from the project root: php examples/exercise-compatibility.php <path/to/composer.json> [...]
// Offline: it reads files and Composer's installed metadata, never the network or a model.
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use Composer\InstalledVersions;

/** @return list<int> the major versions a constraint such as "^3.17" or "^3.0|^4.0" names */
function majorsOf(string $constraint): array
{
    preg_match_all('/(?:^|[|,\s])[\^~>=v]*\s*(\d+)\./', $constraint, $matches);
    $majors = array_values(array_unique(array_map('intval', $matches[1])));
    sort($majors);
    return $majors;
}

$installed = (string) InstalledVersions::getPrettyVersion('neuron-core/neuron-ai');
$ourMajor = (int) ltrim($installed, 'v');
echo "Our application: neuron-core/neuron-ai {$installed}" . PHP_EOL;

$manifests = array_slice($argv, 1);
if ($manifests === []) {
    fwrite(STDERR, 'Usage: php examples/exercise-compatibility.php <composer.json> [...]' . PHP_EOL);
    exit(1);
}
foreach ($manifests as $path) {
    $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    $name = $manifest['name'] ?? basename(dirname($path));
    $license = is_array($manifest['license'] ?? null) ? implode(', ', $manifest['license']) : ($manifest['license'] ?? 'none');
    $constraint = $manifest['require']['neuron-core/neuron-ai'] ?? null;
    echo PHP_EOL . "Package: {$name}" . PHP_EOL;
    echo "License: {$license}" . PHP_EOL;
    if ($constraint === null) {
        echo 'NeuronAI: not required directly; check its dependencies' . PHP_EOL;
        continue;
    }
    $majors = majorsOf($constraint);
    echo "NeuronAI constraint: {$constraint} (majors " . implode(', ', $majors) . ')' . PHP_EOL;
    echo in_array($ourMajor, $majors, true)
        ? 'Verdict: same major as our application; still review its tools and tests' . PHP_EOL
        : "Verdict: not a drop-in for major {$ourMajor}; isolate it or wait for a compatible release" . PHP_EOL;
}
echo PHP_EOL . 'Compatibility review complete.' . PHP_EOL;
