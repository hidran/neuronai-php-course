<?php
declare(strict_types=1);
use App\Evidence\EvidenceLog;
use App\Tools\ComposerManifestTool;
require __DIR__ . '/../vendor/autoload.php';
$fixtures = array_slice($argv, 1);
foreach ($fixtures as $fixture) {
    $tool = new ComposerManifestTool(
        __DIR__ . "/../fixtures/{$fixture}",
        new EvidenceLog(),
    );
    $envelope = json_decode($tool(), true, flags: JSON_THROW_ON_ERROR);
    echo "{$fixture}: ", json_encode($envelope,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
}
echo 'Manifest checked: ' . implode(', ', $fixtures) . PHP_EOL;
