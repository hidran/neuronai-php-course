<?php
declare(strict_types=1);
use App\Evidence\EvidenceLog;
use App\Tools\GitHistoryTool;
require __DIR__ . '/../vendor/autoload.php';
$passed = 0;
$check = function (string $name, bool $ok) use (&$passed): void {
    $passed += $ok ? 1 : 0;
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
};
$fixtures = __DIR__ . '/../fixtures';
$tool = new GitHistoryTool("{$fixtures}/library", new EvidenceLog());
$check('fixed executable and options', $tool->command(30) === [
    'git', '--no-pager', 'log', '--no-color', '--no-decorate',
    '--max-count=101', '--since=30.days.ago',
    '--format=%h%x1f%as%x1f%an%x1f%s%x1e',
]);
$check('negative days clamp to 1',
    $tool->command(-5)[6] === '--since=1.days.ago');
$check('huge days clamp to 365',
    $tool->command(99999)[6] === '--since=365.days.ago');
$recent = json_decode($tool(30), true);
$check('known recent commit', in_array(
    'Trim repeated separators in slugs',
    array_column($recent['facts']['commits'], 'subject'),
    true,
));
$none = new GitHistoryTool("{$fixtures}/no-history", new EvidenceLog());
$missing = json_decode($none(30), true);
$check('no history is unavailable',
    $missing['status'] === 'unavailable');
echo 'Newest commit: ' . json_encode($recent['facts']['commits'][0],
    JSON_UNESCAPED_SLASHES) . PHP_EOL;
echo "Git checks: {$passed} of 5 passed\n";
