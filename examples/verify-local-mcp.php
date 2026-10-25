<?php
declare(strict_types=1);
// Offline check of part two (FakeAIProvider, no key): discovery through the real fixture over stdio, one cached
// connector, a direct invocation, the agent's flat tool list and a stream whose tool call reaches the server.
// Self-contained: every class it loads and the fixture are copies in this folder; the lab gives only vendor/.
// Usage: php verify-local-mcp.php <lab>
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/PackageVersionTool.php';
require __DIR__ . '/StreamProgress.php';
require __DIR__ . '/ToolStreamConsumer.php';
require __DIR__ . '/McpConnection.php';
require __DIR__ . '/OfficeHoursAgent.php';
use App\Agents\OfficeHoursAgent;
use App\Mcp\McpConnection;
use App\Streaming\StreamProgress;
use App\Streaming\ToolStreamConsumer;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\MCP\McpTool;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
$connection = McpConnection::local(__DIR__ . '/office-hours.php')
    ->only(['get_office_hours']);
$tools = $connection->tools();
if (count($tools) !== 1 || !$tools[0] instanceof McpTool
    || $tools[0]->getName() !== 'get_office_hours') {
    throw new RuntimeException('discovery failed');
}
if ($connection->connector() !== $connection->connector()) {
    throw new RuntimeException('connector must be built once');
}
$result = $connection->connector()->invokeTool(
    ['name' => 'get_office_hours'], ['location' => 'north']);
if ($result->isError() || !str_contains($result->getText(), '09:00-17:00')) {
    throw new RuntimeException('direct invocation: ' . $result->getText());
}
$call = new ToolCall('get_office_hours', 'c1', ['location' => 'north']);
$fake = new FakeAIProvider(
    new ToolCallMessage(null, [$call]),
    new AssistantMessage('North is open 09:00 to 17:00, Europe/Rome.'),
);
$agent = OfficeHoursAgent::make($fake, $connection->connector())
    ->setThreadId('t');
$names = array_map(fn ($t) => $t->getName(), $agent->getTools());
if ($names !== ['get_package_version', 'get_office_hours']) {
    throw new RuntimeException('tools: ' . implode(',', $names));
}
$view = new StreamProgress(diagnostic: true);
ob_start();
$state = new ToolStreamConsumer($view)
    ->consume($agent->stream(new UserMessage('North office hours?')));
$output = ob_get_clean();
if ($state === null || $view->completed() !== ['c1' => 'get_office_hours']
    || !str_contains($output, '09:00-17:00')) {
    throw new RuntimeException("stream: {$output}");
}
echo 'LOCAL MCP OK' . PHP_EOL;
