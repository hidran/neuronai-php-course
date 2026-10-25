<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Media\BenchmarkReport;
use App\Media\BenchmarkRunner;
use App\Media\FixtureSet;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$root = dirname(__DIR__);
$fixtures = FixtureSet::load($root . '/fixtures/multimodal-cost');
$expected = [
    'heading' => 'HARBOR-731',
    'smallLabel' => 'COVE-592',
    'quantity' => 18,
];
$runner = new BenchmarkRunner(
    $provider, $fixtures, $fixtures->question, $expected,
);
$report = new BenchmarkReport($root . '/storage/multimodal-cost');
$report->header($settings, $fixtures->question);
$rows = [];
foreach ($fixtures->cases as $case) {
    $row = $runner->run($case);
    $report->row($row);
    $rows[] = $row;
}
$report->finish($rows, $settings);
