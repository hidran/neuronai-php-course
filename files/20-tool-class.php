<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Config\ProviderFactory;
use App\Tools\CountMessagesTool;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$memory = new PDO('sqlite::memory:');
$memory->exec('CREATE TABLE chat_messages (thread_id TEXT)');
$memory->exec("INSERT INTO chat_messages VALUES ('demo'), ('demo')");
$test = new CountMessagesTool($memory);
echo 'Test call: ' . $test('demo') . PHP_EOL;
$pdo = require __DIR__ . '/database.php';
$tool = new CountMessagesTool($pdo);
$question = $argv[1] ?? 'How many messages are stored on thread bob?';
$state = AssistantAgent::make($provider)
    ->addTool($tool)
    ->setThreadId('tool-class')
    ->chat(new UserMessage($question));
echo $state->getMessage()?->getContent() . PHP_EOL;
foreach ($state->getSteps() as $i => $step) {
    echo 'Step ' . ($i + 1) . ': ' . $step::class . PHP_EOL;
}
