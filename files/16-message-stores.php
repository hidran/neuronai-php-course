<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Config\StorageSettings;
use App\HelloAgent;
use App\Support\Database;
use App\Support\MessageStoreFactory;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$storage = StorageSettings::fromEnv($_ENV, dirname(__DIR__));
$database = new Database(
    $storage->dsn, $storage->user, $storage->password,
);
$store = new MessageStoreFactory($storage, $database)->create();
[$thread, $text] = [$argv[1] ?? 'demo', $argv[2] ?? 'Hi!'];
$agent = HelloAgent::make($provider)
    ->setMessageStore($store)
    ->setThreadId($thread);
$reply = $agent->chat(new UserMessage($text))->getMessage();
echo 'Answer: ' . $reply?->getContent() . PHP_EOL;
$count = count($agent->getChatHistory()->getMessages());
echo "{$storage->chatStore} store, thread {$thread}: "
    . "{$count} messages" . PHP_EOL;
