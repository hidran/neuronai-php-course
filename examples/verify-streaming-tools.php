<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/ServerConfigurationTool.php';
require __DIR__ . '/ServerAgent.php';
require __DIR__ . '/StreamProgress.php';
require __DIR__ . '/ToolStreamConsumer.php';
use App\Agents\ServerAgent;
use App\Streaming\StreamProgress;
use App\Streaming\ToolStreamConsumer;
use NeuronAI\Agent\AgentState;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
$call = new ToolCall('get_server_configuration', 'c1',
    ['server' => 'training']);
$fake = new FakeAIProvider(
    new ToolCallMessage(null, [$call]),
    new AssistantMessage('The training server is 192.0.2.42.'),
);
$fake->setStreamChunkSize(6);
$view = new StreamProgress(diagnostic: true);
$stream = ServerAgent::make($fake)->setThreadId('t')
    ->stream(new UserMessage('Training server address?'));
ob_start();
$state = new ToolStreamConsumer($view)->consume($stream);
$output = ob_get_clean();
if (!$state instanceof AgentState) {
    throw new RuntimeException("no state: {$output}");
}
$labels = ['Arguments so far: {"serv', 'Call c1: get_server_config',
    'Result: {"server":"training"', '192.0.2.42',
    'Tool runs: 1, pending: 0', 'Tool stream complete.'];
foreach ($labels as $expected) {
    if (!str_contains($output, $expected)) {
        throw new RuntimeException("missing '{$expected}': {$output}");
    }
}
if ($view->completed() !== ['c1' => 'get_server_configuration']) {
    throw new RuntimeException('completed calls differ');
}
if ($fake->getCallCount() !== 2) {
    throw new RuntimeException('expected two streamed calls');
}
echo 'STREAMING TOOLS OK' . PHP_EOL;
