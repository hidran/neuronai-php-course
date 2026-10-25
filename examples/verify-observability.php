<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/MonitoringSettings.php';
require __DIR__ . '/FixtureWeatherTool.php';
require __DIR__ . '/ObservedWeatherAgent.php';
require __DIR__ . '/EventFileLogger.php';
require __DIR__ . '/AgentMonitor.php';
use App\Agents\ObservedWeatherAgent;
use App\Config\MonitoringSettings;
use App\Observability\AgentMonitor;
use App\Observability\EventFileLogger;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
try {
    MonitoringSettings::fromEnv([]);
    throw new LogicException('missing key accepted');
} catch (RuntimeException $error) {
    if ($error->getMessage() !== 'INSPECTOR_INGESTION_KEY is not set') {
        throw $error;
    }
}
if (MonitoringSettings::fromEnv(['INSPECTOR_INGESTION_KEY' => 'k'])
    ->ingestionKey !== 'k') {
    throw new RuntimeException('key not read');
}
$fake = new FakeAIProvider(
    new ToolCallMessage(null, [
        new ToolCall('fixture_temperature', 'c1', ['city' => 'Turin']),
    ]),
    new AssistantMessage('Turin is at 18 Celsius.'),
);
$log = tempnam(sys_get_temp_dir(), 'events');
$agent = new AgentMonitor(new EventFileLogger($log))->attach(
    ObservedWeatherAgent::make($fake)->setThreadId('t')
);
ob_start();
$state = $agent->chat(new UserMessage('Turin temperature?'));
$output = ob_get_clean();
if (!str_contains($output, 'Event: ')
    || !str_contains($output, 'Completed tool: fixture_temperature')
    || !str_contains($output, '"city":"Turin"')) {
    throw new RuntimeException("console: {$output}");
}
$lines = file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
unlink($log);
if (count($lines) < 4 || !str_starts_with($lines[0], 'info ')) {
    throw new RuntimeException('log: ' . implode('|', $lines));
}
if ($state->getToolRuns('fixture_temperature') !== 1) {
    throw new RuntimeException('tool did not run');
}
$weak = new ObservedWeatherAgent($fake, new \App\Tools\FixtureWeatherTool(
    'Gets weather.'));
if ($weak->getTools()[0]->getDescription() !== 'Gets weather.') {
    throw new RuntimeException('description override lost');
}
echo 'OBSERVABILITY OK' . PHP_EOL;
