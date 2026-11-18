<?php
declare(strict_types=1);
use App\Evidence\EvidenceLog;
use App\Tools\ComposerAuditTool;
require __DIR__ . '/../vendor/autoload.php';
$fixtures = __DIR__ . '/../fixtures';
$cases = [
    'missing lock' => new ComposerAuditTool(
        "{$fixtures}/no-history", new EvidenceLog()),
    'simulated timeout' => new ComposerAuditTool(
        "{$fixtures}/library", new EvidenceLog(),
        composer: "{$fixtures}/bin/slow-composer", timeout: 1.0),
];
$passed = 0;
foreach ($cases as $name => $tool) {
    $envelope = json_decode($tool(), true, flags: JSON_THROW_ON_ERROR);
    $ok = $envelope['status'] === 'unavailable';
    $passed += $ok ? 1 : 0;
    echo ($ok ? 'PASS ' : 'FAIL ')
        . "{$name}: {$envelope['status']}, {$envelope['reason']}\n";
}
echo "Unavailable checks: {$passed} of " . count($cases) . " passed\n";
