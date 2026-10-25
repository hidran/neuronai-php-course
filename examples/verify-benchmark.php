<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/../../01-05-keys-and-environment/files/Provider.php';
require __DIR__ . '/../../01-05-keys-and-environment/files/Settings.php';
require __DIR__ . '/../../08-01-media-content-blocks/files/MediaAgent.php';
foreach (['ImageCase', 'Measurement', 'CostProbe', 'FixtureSet',
    'BenchmarkRow', 'BenchmarkRunner', 'BenchmarkReport'] as $class) {
    require __DIR__ . "/{$class}.php";
}
use App\Config\Provider;
use App\Config\Settings;
use App\Media\BenchmarkReport;
use App\Media\BenchmarkRunner;
use App\Media\FixtureSet;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Testing\FakeAIProvider;
$fixtures = FixtureSet::load(__DIR__);
if (count($fixtures->cases) !== 3 || $fixtures->cases[0]->name !== 'full') {
    throw new RuntimeException('manifest cases changed');
}
$good = '{"heading":"HARBOR-731","smallLabel":"COVE-592","quantity":18}';
$bad = '{"heading":"HARBOR-731","smallLabel":null,"quantity":null}';
$fake = new FakeAIProvider(new AssistantMessage($good),
    new AssistantMessage($good), new AssistantMessage($bad));
$runner = new BenchmarkRunner($fake, $fixtures, $fixtures->question,
    ['heading' => 'HARBOR-731', 'smallLabel' => 'COVE-592',
        'quantity' => 18]);
$rows = array_map($runner->run(...), $fixtures->cases);
if (!$rows[0]->passed() || !$rows[1]->passed() || $rows[2]->passed()) {
    throw new RuntimeException('pass/fail evaluation wrong');
}
if ($rows[2]->checks !== ['heading' => true, 'smallLabel' => false,
    'quantity' => false]) {
    throw new RuntimeException('per key checks wrong');
}
$sent = $fake->getRecorded()[0]->messages[0]->getContentBlocks();
if (count($sent) !== 2 || !$sent[1] instanceof ImageContent
    || $fake->getRecorded()[0]->method !== 'structured') {
    throw new RuntimeException('image not sent through structured()');
}
if ($rows[0]->measurement->rawBytes <= $rows[2]->measurement->rawBytes) {
    throw new RuntimeException('full must be larger than tiny');
}
$settings = new Settings(Provider::Anthropic, 'fake-model', 'k');
$storage = sys_get_temp_dir() . '/bench-' . bin2hex(random_bytes(4));
$report = new BenchmarkReport($storage);
ob_start();
$report->header($settings, $fixtures->question);
foreach ($rows as $row) {
    $report->row($row);
}
$report->finish($rows, $settings);
$output = ob_get_clean();
foreach (['Provider: anthropic | Model: fake-model', 'PASS 3/3',
    'FAIL 1/3', 'Smallest passing file this run: medium',
    'Measurement complete.'] as $expected) {
    if (!str_contains($output, $expected)) {
        throw new RuntimeException("missing '{$expected}': {$output}");
    }
}
$saved = json_decode(file_get_contents($storage . '/latest.json'), true);
if (count($saved['rows']) !== 3 || $saved['rows'][2]['passed'] !== false) {
    throw new RuntimeException('report file wrong');
}
array_map(unlink(...), glob($storage . '/*'));
rmdir($storage);
echo 'BENCHMARK OK' . PHP_EOL;
