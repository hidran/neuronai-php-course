<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Config\ProviderFactory;
use App\Tools\CountMessagesTool;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Tools\ToolOutput;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$mode = $argv[1] ?? 'plain';
echo "Mode: {$mode}" . PHP_EOL;
$empty = new PDO('sqlite::memory:');
$agent = AssistantAgent::make($provider)
    ->addTool(new CountMessagesTool($empty))
    ->setThreadId('tool-errors');
if ($mode === 'safe') {
    $agent->toolErrorHandler(
        fn (Throwable $e) => $e instanceof PDOException
            ? ToolOutput::error('The chat database is not set up'
                . ' yet. Tell the user, and do not retry.')
            : null,
    );
}
try {
    $question = 'How many messages are stored on thread bob?';
    $state = $agent->chat(new UserMessage($question));
    foreach ($state->getSteps() as $step) {
        echo 'Step: ' . $step::class . PHP_EOL;
        if ($step instanceof ToolResultMessage) {
            foreach ($step->getToolCalls() as $call) {
                $json = json_encode($call->getResult());
                echo "Tool result: {$json}" . PHP_EOL;
            }
        }
    }
    echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
} catch (PDOException $e) {
    echo "Run aborted: {$e->getMessage()}" . PHP_EOL;
}
