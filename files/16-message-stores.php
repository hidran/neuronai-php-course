<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\History\FileMessageStore;
use NeuronAI\Chat\History\InMemoryMessageStore;
use NeuronAI\Chat\History\SQLMessageStore;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
[, $kind, $thread, $text] = $argv;
$storage = dirname(__DIR__) . '/storage';
function sqlStore(string $storage): SQLMessageStore
{
    is_dir($storage) || mkdir($storage);
    $pdo = new PDO("sqlite:{$storage}/chat.sqlite");
    $columns = [
        'id INTEGER PRIMARY KEY AUTOINCREMENT',
        'thread_id VARCHAR(255) NOT NULL',
        'message_id VARCHAR(64) NOT NULL',
        'role VARCHAR(32) NOT NULL',
        'content TEXT',
        'meta TEXT',
        'archived_at TIMESTAMP',
        'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'UNIQUE (thread_id, message_id)',
    ];
    $pdo->exec('CREATE TABLE IF NOT EXISTS chat_messages ('
        . implode(', ', $columns) . ')');
    return new SQLMessageStore(pdo: $pdo, table: 'chat_messages');
}
$store = match ($kind) {
    'memory' => new InMemoryMessageStore(),
    'file' => new FileMessageStore(directory: $storage, ext: '.json'),
    'sql' => sqlStore($storage),
};
$agent = HelloAgent::make()
    ->setMessageStore($store)
    ->setThreadId($thread);
$reply = $agent->chat(new UserMessage($text))->getMessage();
echo 'Answer: ' . $reply?->getContent() . PHP_EOL;
$count = count($agent->getChatHistory()->getMessages());
echo "{$kind} store, thread {$thread}: {$count} messages" . PHP_EOL;
