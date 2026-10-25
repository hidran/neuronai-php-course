<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Config\ProviderFactory;
use App\Tools\ProjectToolkit;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Tools\Toolkits\Calculator\CalculatorToolkit;
$settings = require __DIR__ . '/bootstrap.php';
$pdo = require __DIR__ . '/database.php';
$kit = new ProjectToolkit($pdo);
if ($argc < 2) {
    foreach ([CalculatorToolkit::make(), $kit] as $toolkit) {
        $names = [];
        foreach ($toolkit->tools() as $tool) {
            $names[] = $tool->getName();
        }
        echo count($names) . ' tools: '
            . implode(', ', $names) . PHP_EOL;
        echo $toolkit->guidelines() . PHP_EOL;
    }
    exit;
}
$provider = new ProviderFactory($settings)->create();
$state = AssistantAgent::make($provider)
    ->addTool($kit)
    ->setThreadId('toolkits')
    ->chat(new UserMessage($argv[1]));
foreach ($state->getSteps() as $step) {
    if ($step instanceof ToolCallMessage) {
        foreach ($step->getToolCalls() as $call) {
            echo "Model called: {$call->getName()}" . PHP_EOL;
        }
    }
}
echo $state->getMessage()?->getContent() . PHP_EOL;
