<?php

declare(strict_types=1);
$lab = $argv[1] ?? dirname(__DIR__);
require $lab . '/vendor/autoload.php';
// CountMessagesTool.php autoloads from src/Tools/CountMessagesTool.php
// AssistantAgent.php autoloads from src/Agents/AssistantAgent.php

use App\Agents\AssistantAgent;
use App\Tools\CountMessagesTool;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
use NeuronAI\Tools\ToolOutput;

$call = fn() => new ToolCallMessage(null, [new ToolCall('count_messages', 'c1', ['thread' => 'bob'])]);
$build = function (bool $safe) use ($call): AssistantAgent {
    $fake = new FakeAIProvider($call(), new AssistantMessage('Sorry, no database.'));
    $agent = AssistantAgent::make($fake)->addTool(new CountMessagesTool(new PDO('sqlite::memory:')))->setThreadId('t');
    if ($safe) {
        $agent->toolErrorHandler(fn(Throwable $e) => $e instanceof PDOException ? ToolOutput::error('db down') : null);
    }
    return $agent;
};
try {
    $build(false)->chat(new UserMessage('q'));
    throw new RuntimeException('plain mode should propagate PDOException');
} catch (PDOException) {
    echo 'plain: PDOException propagated' . PHP_EOL;
}
$state = $build(true)->chat(new UserMessage('q'));
if ($state->getMessage()?->getContent() !== 'Sorry, no database.') {
    throw new RuntimeException('safe mode did not settle the error');
}
echo 'TOOL ERRORS OK' . PHP_EOL;
