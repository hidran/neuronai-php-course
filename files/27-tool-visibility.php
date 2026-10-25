<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Config\ProviderFactory;
use App\PackageVersionTool;
use App\Tools\CountMessagesTool;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$pdo = require __DIR__ . '/database.php';
$role = $argv[1] ?? 'user';
echo "Role: {$role}" . PHP_EOL;
$counter = new CountMessagesTool($pdo)->visible($role === 'admin');
$agent = AssistantAgent::make($provider)
    ->addTool([new PackageVersionTool(), $counter])
    ->setThreadId("visibility-{$role}");
foreach ($agent->getTools() as $tool) {
    $shown = $tool->isVisible() ? 'sent to the model' : 'hidden';
    echo "Tool {$tool->getName()}: {$shown}" . PHP_EOL;
}
$state = $agent->chat(
    new UserMessage('How many messages are on thread bob?'),
);
foreach ($state->getSteps() as $step) {
    if ($step instanceof ToolCallMessage) {
        foreach ($step->getToolCalls() as $call) {
            echo "Model called: {$call->getName()}" . PHP_EOL;
        }
    }
}
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
