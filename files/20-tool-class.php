<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Tools\CountMessagesTool;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$memory = new PDO('sqlite::memory:');
$memory->exec('CREATE TABLE chat_messages (thread_id TEXT)');
$memory->exec("INSERT INTO chat_messages VALUES ('demo'), ('demo')");
$test = CountMessagesTool::make($memory);
echo 'Test call: ' . $test('demo') . PHP_EOL;
$file = dirname(__DIR__) . '/storage/assistant/chat.sqlite';
$tool = CountMessagesTool::make(new PDO("sqlite:{$file}"));
$question = $argv[1] ?? 'How many messages are stored on thread bob?';
$state = AssistantAgent::make()
    ->addTool($tool)
    ->setThreadId('tool-class')
    ->chat(new UserMessage($question));
echo $state->getMessage()?->getContent() . PHP_EOL;
foreach ($state->getSteps() as $i => $step) {
    echo 'Step ' . ($i + 1) . ': ' . $step::class . PHP_EOL;
}
