<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Tools\CheckPackagesTool;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$tool = CheckPackagesTool::make();
$flags = JSON_UNESCAPED_SLASHES;
if ($argc < 2) {
    $schema = $tool->getInputSchema();
    echo json_encode($schema, $flags | JSON_PRETTY_PRINT) . PHP_EOL;
    exit;
}
$state = AssistantAgent::make()
    ->addTool($tool)
    ->setThreadId('property-types')
    ->chat(new UserMessage($argv[1]));
foreach ($state->getSteps() as $step) {
    if ($step instanceof ToolCallMessage) {
        foreach ($step->getToolCalls() as $call) {
            $inputs = json_encode($call->getInputs(), $flags);
            echo "Model sent: {$inputs}" . PHP_EOL;
        }
    }
}
echo $state->getMessage()?->getContent() . PHP_EOL;
echo 'Steps: ' . count($state->getSteps()) . PHP_EOL;
