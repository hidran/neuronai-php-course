<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Media\BenchmarkReport;
use App\Media\BenchmarkRow;
use App\Media\BenchmarkRunner;
use App\Media\FixtureSet;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$root = dirname(__DIR__);
$fixtures = FixtureSet::load($root . '/fixtures/multimodal-cost');
$runner = new BenchmarkRunner($provider, $fixtures,
    $fixtures->exerciseQuestion, [
        'heading' => 'HARBOR-731',
        'smallLabel' => 'COVE-592',
        'quantity' => 42,
    ]);
$report = new BenchmarkReport($root . '/storage/multimodal-cost');
$report->header($settings, $fixtures->exerciseQuestion);
$rows = [];
foreach ($fixtures->cases as $case) {
    $row = $runner->run($case);
    $report->row($row);
    $rows[] = $row;
}
$report->finish($rows, $settings);
$threads = array_map(static fn (BenchmarkRow $r): string => $r->thread,
    $rows);
if (count(array_unique($threads)) !== count($rows)) {
    throw new RuntimeException('Requests shared a thread.');
}
$full = array_find($rows,
    static fn (BenchmarkRow $r): bool => $r->variant === 'full');
if ($full === null || !$full->passed()) {
    throw new RuntimeException('Full image failed the new task.');
}
foreach ($rows as $row) {
    if ($row->seconds < 0
        || $row->measurement->base64Bytes
            < $row->measurement->rawBytes) {
        throw new RuntimeException('Invalid measurement units.');
    }
}
echo 'Distinct Bracket row task verified.' . PHP_EOL;
echo 'Fresh threads and measurement units verified.' . PHP_EOL;
echo 'Exercise complete.' . PHP_EOL;
