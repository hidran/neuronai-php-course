<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\ObservedWeatherAgent;
use App\Config\MonitoringSettings;
use App\Config\ProviderFactory;
use App\Observability\Prices;
use App\Observability\TraceRecorder;
use App\Observability\TraceReport;
use App\Tools\FixtureWeatherTool;
use Inspector\Configuration;
use Inspector\Inspector;
use Inspector\Neuron\V4\InspectorSubscriber;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Observability\ObservabilityEvent;
$settings = require __DIR__ . '/bootstrap.php';
$root = dirname(__DIR__);
$name = $argv[1] ?? 'baseline';
$mode = $argv[2] ?? 'local';
if (!in_array($name, ['baseline', 'modified', 'exercise'], true)
    || !in_array($mode, ['local', 'inspector', 'show'], true)) {
    throw new InvalidArgumentException('Choose documented arguments.');
}
$path = $root . '/storage/trace-' . $name . '.json';
if ($mode === 'show') {
    TraceReport::load($path)->show();
    exit;
}
$question = 'Use the fixture temperatures for Turin and Milan '
    . 'and calculate their average with the mean tool.';
$label = 'weather-trace-' . $name . '-' . gmdate('Ymd-His');
$weather = $name === 'modified'
    ? new FixtureWeatherTool('Gets weather.')
    : new FixtureWeatherTool();
$provider = new ProviderFactory($settings)->create();
$agent = ObservedWeatherAgent::make($provider, $weather)
    ->setThreadId($label);
$prices = new Prices([
    'claude-sonnet-5-5' => ['input' => 3.0, 'output' => 15.0],
]);
$recorder = new TraceRecorder($prices, $settings->model);
$agent->subscribe(ObservabilityEvent::class, $recorder);
$inspector = null;
if ($mode === 'inspector') {
    $monitoring = MonitoringSettings::fromEnv($_ENV);
    $configuration = new Configuration($monitoring->ingestionKey);
    $configuration->setTransport('sync');
    $inspector = new Inspector($configuration);
    $inspector->startTransaction($label)
        ->addContext('Teaching run', ['question' => $question]);
    $agent->subscribe(ObservabilityEvent::class,
        new InspectorSubscriber($inspector));
}
echo 'Run label: ' . $label . PHP_EOL;
try {
    $state = $agent->chat(new UserMessage($question));
    $report = TraceReport::fromRun($label, $question, $agent, $recorder,
        $state->getMessage()?->getContent());
    $inspector?->transaction()->addContext('Trace', $report->data);
} finally {
    $inspector?->flush();
}
$report->save($path);
$report->show();
