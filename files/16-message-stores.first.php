<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\History\FileMessageStore;
use NeuronAI\Chat\History\InMemoryMessageStore;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
[, $kind, $thread, $text] = $argv;
$storage = dirname(__DIR__) . '/storage';
$store = match ($kind) {
    'memory' => new InMemoryMessageStore(),
    'file' => new FileMessageStore(directory: $storage, ext: '.json'),
};
$agent = HelloAgent::make()
    ->setMessageStore($store)
    ->setThreadId($thread);
$reply = $agent->chat(new UserMessage($text))->getMessage();
echo 'Answer: ' . $reply?->getContent() . PHP_EOL;
$count = count($agent->getChatHistory()->getMessages());
echo "{$kind} store, thread {$thread}: {$count} messages" . PHP_EOL;
