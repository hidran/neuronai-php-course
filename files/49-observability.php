<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\ObservedWeatherAgent;
use App\Observability\AgentMonitoring;
use App\Observability\MonitoredAgentFactory;
use Dotenv\Dotenv;
use Inspector\Inspector;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$mode = $argv[1] ?? 'local';
$allowed = ['local', 'tools', 'log', 'inspector', 'factory'];
if (!in_array($mode, $allowed, true)) {
    throw new InvalidArgumentException('Choose a documented mode.');
}
$thread = 'inspector-weather-' . gmdate('Ymd-His');
$log = dirname(__DIR__) . '/storage/events.log';
Inspector::beforeFlush(function (Inspector $inspector) use ($thread): void {
    $transaction = $inspector->transaction();
    if ($transaction === null) {
        return;
    }
    $transaction->name = $thread;
    $transaction->addContext('Teaching run label', $thread);
    $metadata = ['label' => $thread, 'group' => $thread,
        'hash' => $transaction->hash,
        'utc' => gmdate(DATE_ATOM)];
    file_put_contents(dirname(__DIR__) . '/storage/inspector-latest.json',
        json_encode($metadata, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo 'Inspector transaction hash: ' . $transaction->hash . PHP_EOL;
});
if ($mode === 'factory') {
    $agent = MonitoredAgentFactory::make($thread);
} else {
    $agent = ObservedWeatherAgent::make()->setThreadId($thread);
    AgentMonitoring::local($agent);
    if ($mode !== 'local') {
        AgentMonitoring::tools($agent);
    }
    if ($mode === 'log') {
        AgentMonitoring::logger($agent, $log);
    }
    if ($mode === 'inspector') {
        AgentMonitoring::backend($agent);
    }
}
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
