<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Tools\FindPackagesTool;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$tool = FindPackagesTool::make();
if ($argc < 2) {
    $schema = $tool->getInputSchema();
    echo json_encode($schema, JSON_PRETTY_PRINT) . PHP_EOL;
    exit;
}
$state = AssistantAgent::make()
    ->addTool($tool)
    ->setThreadId('schema-property')
    ->chat(new UserMessage($argv[1]));
foreach ($state->getSteps() as $step) {
    if ($step instanceof ToolCallMessage) {
        foreach ($step->getToolCalls() as $call) {
            $inputs = $call->getInputs();
            echo 'Model sent: ' . json_encode($inputs) . PHP_EOL;
            $filter = $tool->setInputs($inputs)->getInput('filter');
            echo 'Invoke got: ' . print_r($filter, true);
        }
    }
}
echo $state->getMessage()?->getContent() . PHP_EOL;
echo 'Steps: ' . count($state->getSteps()) . PHP_EOL;
