<?php
declare(strict_types=1);
use App\Evidence\AdvisoryNormalizer;
require __DIR__ . '/../vendor/autoload.php';
$captured = __DIR__ . '/../fixtures/composer-audit';
$cases = [
    'clean.json' => [0, 'clean'],
    'advisories.json' => [1, 'findings'],
    'abandoned.json' => [2, 'findings'],
    'malformed.txt' => [1, 'unavailable'],
];
$passed = 0;
foreach ($cases as $file => [$exitCode, $expected]) {
    $result = new AdvisoryNormalizer()->normalize(
        (string) file_get_contents("{$captured}/{$file}"), $exitCode);
    $status = $result['status'] ?? 'unavailable';
    $facts = $result['facts'] ?? [];
    $passed += $status === $expected ? 1 : 0;
    printf("%s %-16s exit %d: %s, advisories %d, abandoned %d%s\n",
        $status === $expected ? 'PASS' : 'FAIL', $file, $exitCode,
        $status, $facts['advisory_count'] ?? 0,
        count($facts['abandoned'] ?? []),
        isset($facts['advisories'][1])
            ? ", severities {$facts['advisories'][0]['severity']}/"
                . $facts['advisories'][1]['severity'] : '');
}
echo "Parser checks: {$passed} of " . count($cases) . " passed\n";
