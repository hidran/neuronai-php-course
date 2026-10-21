<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\DataAnalystAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Tools\ToolOutput;
Dotenv::createImmutable(dirname(__DIR__))->load();
$pdo = require __DIR__ . '/../database/connect.php';
$question = $argv[1] ?? 'What is our October 2026 revenue'
    . ' and order count?';
$agent = DataAnalystAgent::make($pdo)
    ->toolMaxRuns(5)
    ->toolErrorHandler(fn () => ToolOutput::error(
        'Data unavailable. Stop and report that to the user.'
    ))
    ->setThreadId('database-analyst');
$state = $agent->chat(new UserMessage($question));
foreach ($state->getSteps() as $step) {
    if (!$step instanceof ToolResultMessage) {
        continue;
    }
    foreach ($step->getToolCalls() as $call) {
        echo 'Tool: ' . $call->getName() . PHP_EOL;
        if ($call->getName() === 'mysql_select_query') {
            $input = json_encode($call->getInputs());
            echo 'SQL input: ' . $input . PHP_EOL;
            echo 'Rows: ' . $call->getResult() . PHP_EOL;
        }
    }
}
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
echo 'ANALYSIS COMPLETE' . PHP_EOL;
