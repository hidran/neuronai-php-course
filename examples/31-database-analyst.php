<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\DataAnalystAgent;
use App\Config\ProviderFactory;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Tools\ToolOutput;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$pdo = require __DIR__ . '/database.php';
$question = $argv[1] ?? 'What is our October 2026 revenue'
    . ' and order count?';
$agent = DataAnalystAgent::make($provider, $pdo)
    ->toolMaxRuns(5)
    ->toolErrorHandler(function (Throwable $e): ?ToolOutput {
        if (!$e instanceof PDOException) {
            return null;
        }
        fwrite(STDERR, "Database error: {$e->getMessage()}" . PHP_EOL);
        return ToolOutput::error(
            'Data unavailable. Stop and report that to the user.'
        );
    })
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
