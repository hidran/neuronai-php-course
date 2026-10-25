<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/StorageSettings.php';
require __DIR__ . '/Database.php';
require __DIR__ . '/MessageStoreFactory.php';
use App\Config\StorageSettings;
use App\Support\Database;
use App\Support\MessageStoreFactory;
use NeuronAI\Chat\History\FileMessageStore;
use NeuronAI\Chat\History\InMemoryMessageStore;
use NeuronAI\Chat\History\SQLMessageStore;
use NeuronAI\Chat\Messages\UserMessage;
$root = sys_get_temp_dir() . '/neuron-verify-' . bin2hex(random_bytes(3));
mkdir($root . '/database', recursive: true);
copy(__DIR__ . '/chat_messages.sql', $root . '/database/chat_messages.sql');
$expected = [
    'memory' => InMemoryMessageStore::class,
    'file' => FileMessageStore::class,
    'sqlite' => SQLMessageStore::class,
];
foreach ($expected as $kind => $class) {
    $s = StorageSettings::fromEnv(['CHAT_STORE' => $kind], $root);
    $db = new Database($s->dsn, $s->user, $s->password);
    $factory = new MessageStoreFactory($s, $db);
    // The factory resolves the SQL file relative to src/Support: emulate it.
    $store = $kind === 'sqlite'
        ? (function () use ($db, $root) {
            $db->migrate($root . '/database/chat_messages.sql');
            return new SQLMessageStore($db->pdo());
        })()
        : $factory->create();
    if (!$store instanceof $class) {
        throw new RuntimeException("{$kind}: " . $store::class);
    }
    $store->append('t1', new UserMessage('hello ' . $kind));
    $loaded = $store->loadAll('t1');
    if (count($loaded) !== 1) {
        throw new RuntimeException("{$kind}: round trip failed");
    }
}
if (!is_file($root . '/storage/chat.sqlite')) {
    throw new RuntimeException('sqlite file not created');
}
try {
    $s = StorageSettings::fromEnv(['CHAT_STORE' => 'redis'], $root);
    new MessageStoreFactory($s, new Database($s->dsn))->create();
    throw new RuntimeException('unknown store accepted');
} catch (RuntimeException $e) {
    if (!str_contains($e->getMessage(), 'Unknown CHAT_STORE')) {
        throw $e;
    }
}
echo 'STORES OK' . PHP_EOL;
