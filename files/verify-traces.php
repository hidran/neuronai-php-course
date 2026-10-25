<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/../../10-01-observability-inspector/files/FixtureWeatherTool.php';
require __DIR__ . '/../../10-01-observability-inspector/files/ObservedWeatherAgent.php';
require __DIR__ . '/Prices.php';
require __DIR__ . '/TraceRecorder.php';
require __DIR__ . '/TraceReport.php';
use App\Agents\ObservedWeatherAgent;
use App\Observability\Prices;
use App\Observability\TraceRecorder;
use App\Observability\TraceReport;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\Usage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Observability\ObservabilityEvent;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
$prices = new Prices(['fake-model' => ['input' => 3.0, 'output' => 15.0]]);
$cost = $prices->costUsd('fake-model', new Usage(1_000_000, 100_000));
if ($cost !== 4.5 || $prices->costUsd('other', new Usage(1, 1)) !== null) {
    throw new RuntimeException("cost: {$cost}");
}
$fake = new FakeAIProvider(
    new ToolCallMessage(null, [
        new ToolCall('fixture_temperature', 'c1', ['city' => 'Turin']),
    ]),
    new AssistantMessage('Turin is at 18 Celsius.'),
);
$agent = ObservedWeatherAgent::make($fake)->setThreadId('t');
$recorder = new TraceRecorder($prices, 'fake-model');
$agent->subscribe(ObservabilityEvent::class, $recorder);
$state = $agent->chat(new UserMessage('Turin?'));
if (count($recorder->inferences()) !== 2 || count($recorder->tools()) !== 1
    || $recorder->tools()[0]['result'] !== 'Fixture Turin: 18 Celsius'
    || $recorder->tools()[0]['duration_ms'] === null) {
    throw new RuntimeException('recorder: ' . json_encode($recorder->tools()));
}
$report = TraceReport::fromRun('t', 'Turin?', $agent, $recorder,
    $state->getMessage()?->getContent());
$path = tempnam(sys_get_temp_dir(), 'trace');
$report->save($path);
$loaded = TraceReport::load($path);
unlink($path);
ob_start();
$loaded->show();
$output = ob_get_clean();
if (!str_contains($output, 'Inference requests: 2')
    || !str_contains($output, 'Estimated cost USD: ')
    || !str_contains($output, 'Trace analysis complete.')
    || $loaded->data['offered'][0]['name'] !== 'fixture_temperature') {
    throw new RuntimeException("show: {$output}");
}
echo 'TRACES OK' . PHP_EOL;
