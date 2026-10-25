<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Config\StorageSettings;
use App\HelloAgent;
use App\Support\Database;
use App\Support\FactStore;
use App\Support\MessageStoreFactory;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$storage = StorageSettings::fromEnv($_ENV, dirname(__DIR__));
$database = new Database(
    $storage->dsn, $storage->user, $storage->password,
);
$store = new MessageStoreFactory($storage, $database)->create();
$facts = new FactStore($storage->path);
[$user, $thread, $text] = [
    $argv[1] ?? 'valerio', $argv[2] ?? 'monday', $argv[3] ?? 'Hi!',
];
if (str_starts_with($text, 'Remember: ')) {
    $facts->remember($user, substr($text, strlen('Remember: ')));
}
$memory = array_map(
    fn (string $fact) => new TextContent("Remembered: {$fact}"),
    $facts->recall($user),
);
$agent = HelloAgent::make($provider)
    ->setMessageStore($store)
    ->setThreadId($thread)
    ->setContext(...$memory);
$reply = $agent->chat(new UserMessage($text))->getMessage();
echo 'Answer: ' . $reply?->getContent() . PHP_EOL;
$count = count($agent->getChatHistory()->getMessages());
echo "Thread {$thread}: {$count} messages. Facts about {$user}: "
    . count($memory) . PHP_EOL;
