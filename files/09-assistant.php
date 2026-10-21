<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$question = $argv[1] ?? 'What does composer dump-autoload do?';
$agent = AssistantAgent::make()->setThreadId('assistant');
$state = $agent->chat(new UserMessage($question));
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
echo 'Answered by: ' . $agent->getProvider()::class . PHP_EOL;
