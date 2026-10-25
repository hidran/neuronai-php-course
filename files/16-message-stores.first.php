<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\HelloAgent;
use NeuronAI\Chat\History\InMemoryMessageStore;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
[$thread, $text] = [$argv[1] ?? 'demo', $argv[2] ?? 'Hi!'];
$store = new InMemoryMessageStore();
$agent = HelloAgent::make($provider)
    ->setMessageStore($store)
    ->setThreadId($thread);
$reply = $agent->chat(new UserMessage($text))->getMessage();
echo 'Answer: ' . $reply?->getContent() . PHP_EOL;
$count = count($agent->getChatHistory()->getMessages());
echo "memory store, thread {$thread}: {$count} messages" . PHP_EOL;
