<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/PackageVersionTool.php';
require __DIR__ . '/HelloAgent.php';
use App\HelloAgent;
use App\PackageVersionTool;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
$tool = new PackageVersionTool();
$direct = $tool('vlucas/phpdotenv');
if (!str_contains($direct, 'vlucas/phpdotenv is at version')) {
    throw new RuntimeException("direct call: {$direct}");
}
if (!str_contains($tool('nope/nope'), 'not installed')) {
    throw new RuntimeException('missing package not reported');
}
$schema = json_encode($tool->getInputSchema());
if (!str_contains($schema, '"package"')) {
    throw new RuntimeException("schema: {$schema}");
}
$call = new ToolCallMessage(null, [
    new ToolCall('get_package_version', 'call_1',
        ['package' => 'neuron-core/neuron-ai']),
]);
$fake = new FakeAIProvider($call, new AssistantMessage('It is 4.1.x.'));
$state = HelloAgent::make($fake)->toolMaxRuns(3)->setThreadId('t')
    ->chat(new UserMessage('Which version?'));
if ($state->getMessage()?->getContent() !== 'It is 4.1.x.') {
    throw new RuntimeException('final answer not returned');
}
if ($fake->getCallCount() !== 2) {
    throw new RuntimeException('expected two provider calls');
}
$tools = HelloAgent::make($fake)->getTools();
$names = array_map(fn ($t) => $t->getName(), $tools);
if ($names !== ['get_package_version']) {
    throw new RuntimeException('tool not registered');
}
echo 'FIRST TOOL OK' . PHP_EOL;
