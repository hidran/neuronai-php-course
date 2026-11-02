<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Neuron\Agents\AssistantAgent;
use App\Neuron\Tools\OfficeHours;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Testing\FakeAIProvider;
set_exception_handler(function (Throwable $e): void {
    echo 'Refused: ' . $e->getMessage() . PHP_EOL;
});
$fake = new FakeAIProvider(new AssistantMessage('Fake answer.'));
$show = function (string $name, Agent $agent): void {
    echo "{$name}: " . ($agent->getThreadId() ?? 'none') . PHP_EOL;
};
$tools = function (string $name, Agent $agent): void {
    $names = array_map(fn ($t) => $t->getName(), $agent->getTools());
    $list = implode(', ', $names) ?: 'none';
    echo "{$name} tools: {$list}" . PHP_EOL;
};
$definition = new AssistantAgent($fake);
$show('Definition', $definition);
$aliceAgent = $definition->for('demo-alice');
$aliceAgent->addTool(new OfficeHours());
$show('Alice', $aliceAgent);
printf("Same object: %s\n", json_encode($aliceAgent === $definition));
$bobAgent = $definition->for('demo-bob');
$show('Bob', $bobAgent);
$tools('Alice', $aliceAgent);
$tools('Bob', $bobAgent);
if (($argv[1] ?? '') === 'mistake') {
    $definition->for('demo-carol');
    $definition->chat();
}
