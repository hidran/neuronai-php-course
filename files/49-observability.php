<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\ObservedWeatherAgent;
use App\Config\MonitoringSettings;
use App\Config\ProviderFactory;
use App\Observability\AgentMonitor;
use App\Observability\EventFileLogger;
use Inspector\Neuron\V4\InspectorSubscriber;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$root = dirname(__DIR__);
$mode = $argv[1] ?? 'local';
if (!in_array($mode, ['local', 'log', 'inspector'], true)) {
    throw new InvalidArgumentException('Use local, log or inspector.');
}
$thread = 'inspector-weather-' . gmdate('Ymd-His');
$logger = $mode === 'log'
    ? new EventFileLogger($root . '/storage/events.log') : null;
$inspector = null;
if ($mode === 'inspector') {
    $monitoring = MonitoringSettings::fromEnv($_ENV);
    $inspector = InspectorSubscriber::instance(
        key: $monitoring->ingestionKey, transport: 'sync',
    );
    if (is_file($root . '/storage/inspector-hook.php')) {
        require $root . '/storage/inspector-hook.php';
    }
}
$agent = new AgentMonitor($logger, $inspector)->attach(
    ObservedWeatherAgent::make($provider)->setThreadId($thread)
);
echo "Run label: {$thread}" . PHP_EOL;
echo "Mode: {$mode}; teaching fixture, not live weather" . PHP_EOL;
$question = 'Use the fixture temperatures for Turin and Milan '
    . 'and calculate their average with the mean tool. '
    . 'Run label: ' . $thread;
$state = $agent->chat(new UserMessage($question));
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
if ($mode === 'log') {
    echo 'Name-only application log: storage/events.log' . PHP_EOL;
}
echo 'Observability run complete: ' . $mode . PHP_EOL;
