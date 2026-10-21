<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$agent = HelloAgent::make()->setThreadId('hello');
$state = $agent->chat(
    new UserMessage('What is an AI agent, in plain words?')
);
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
$state = $agent->chat(
    new UserMessage('Now say it again in five words.')
);
echo 'Follow-up: ' . $state->getMessage()?->getContent() . PHP_EOL;
$messages = $agent->getChatHistory()->getMessages();
echo 'Messages in the thread: ' . count($messages) . PHP_EOL;
echo 'Answered by: ' . $agent->getProvider()::class . PHP_EOL;
