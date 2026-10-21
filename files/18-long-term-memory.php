<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\History\FileMessageStore;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
[, $user, $thread, $text] = $argv;
$storage = dirname(__DIR__) . '/storage';
is_dir($storage) || mkdir($storage);
$file = "{$storage}/facts_{$user}.json";
$facts = is_file($file)
    ? json_decode(file_get_contents($file), true)
    : [];
if (str_starts_with($text, 'Remember: ')) {
    $facts[] = substr($text, strlen('Remember: '));
    file_put_contents($file, json_encode($facts));
}
$memory = array_map(
    fn (string $fact) => new TextContent("Remembered: {$fact}"),
    $facts,
);
$agent = HelloAgent::make()
    ->setMessageStore(new FileMessageStore(directory: $storage))
    ->setThreadId($thread)
    ->setContext(...$memory);
$reply = $agent->chat(new UserMessage($text))->getMessage();
echo 'Answer: ' . $reply?->getContent() . PHP_EOL;
$count = count($agent->getChatHistory()->getMessages());
echo "Thread {$thread}: {$count} messages. Facts about {$user}: "
    . count($facts) . PHP_EOL;