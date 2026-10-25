<?php
declare(strict_types=1);
// Offline check of the persistent chat runs (FakeAIProvider, no key): two threads apart in SQLite, the thread
// replayed by a new agent (a new process), /reset clearing one thread only, the same thread name empty in the file
// store, a failed call storing nothing, the unknown store and the thread rule of assistant.php. Self-contained: every
// class it loads is a copy in this folder; the lab gives only vendor/. Usage: php verify-persistent-chat.php <lab>
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/StorageSettings.php';
require __DIR__ . '/Database.php';
require __DIR__ . '/MessageStoreFactory.php';
require __DIR__ . '/AssistantAgent.php';
use App\Agents\AssistantAgent;
use App\Config\StorageSettings;
use App\Support\Database;
use App\Support\MessageStoreFactory;
use NeuronAI\Chat\History\FileMessageStore;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Chat\History\SQLMessageStore;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\NeuronException;
use NeuronAI\Testing\FakeAIProvider;
$root = sys_get_temp_dir() . '/neuron-chat-' . bin2hex(random_bytes(3));
$check = function (bool $ok, string $what): void {
    if (!$ok) {
        throw new RuntimeException($what);
    }
};
// A new SQLite store on the same file: what a new process of assistant.php builds. The factory reads the SQL file
// relative to src/Support, so the copy in this folder is migrated here instead.
$sqlite = function () use ($root): SQLMessageStore {
    $s = StorageSettings::fromEnv(['CHAT_STORE' => 'sqlite'], $root);
    $db = new Database($s->dsn, $s->user, $s->password);
    $db->migrate(__DIR__ . '/chat_messages.sql');
    return new SQLMessageStore($db->pdo());
};
$agent = fn (MessageStoreInterface $store, string $thread, FakeAIProvider $fake): AssistantAgent
    => AssistantAgent::make($fake)->setMessageStore($store)->setThreadId($thread);
$answer = fn (string $text): FakeAIProvider => new FakeAIProvider(new AssistantMessage($text));
$listing = function () use ($root): array {
    $names = array_values(array_diff(scandir($root . '/storage'), ['.', '..']));
    sort($names);
    return $names;
};
try {
    // Part one's end state: alice's first turn, two messages.
    $alice = $agent($sqlite(), 'alice', $answer('Topic: Greeting'));
    $alice->chat(new UserMessage('Hi, my name is Alice, and I am writing a CLI in PHP.'));
    $check(count($sqlite()->loadAll('alice')) === 2, 'alice: one turn must store two messages');
    // Scene 8: bob in argument mode, the same database.
    $agent($sqlite(), 'bob', $answer('Topic: Greeting'))->chat(new UserMessage('Hi, my name is Bob.'));
    $check(count($sqlite()->loadAll('bob')) === 2, 'bob: one turn must store two messages');
    $check($listing() === ['chat.sqlite'], 'storage must hold one file: ' . implode(' ', $listing()));
    // Scene 9: a new process on alice. The earlier turn is replayed to the model with the new question.
    $fake = $answer('Your name is Alice.');
    $store = $sqlite();
    $again = $agent($store, 'alice', $fake);
    $again->chat(new UserMessage('What is my name?'));
    $sent = $fake->getRecorded()[0]->messages ?? [];
    $text = implode(' ', array_map(fn ($m) => (string) $m->getContent(), $sent));
    $check(str_contains($text, 'my name is Alice'), 'the stored turn was not sent again');
    $check(count($store->loadAll('alice')) === 4, 'alice: two turns must store four messages');
    $check(count($again->getChatHistory()->getMessages()) === 4, 'alice: four messages must be sent');
    // Scene 9: /reset clears alice, and only alice.
    $again->resetConversation();
    $check($store->loadAll('alice') === [], 'reset left messages on alice');
    $check($again->getChatHistory()->getMessages() === [], 'reset left messages in the history');
    $check(count($sqlite()->loadAll('bob')) === 2, 'reset touched bob');
    // Scene 10: the same thread name in the file store is another conversation.
    $s = StorageSettings::fromEnv(['CHAT_STORE' => 'file'], $root);
    $file = new MessageStoreFactory($s, new Database($s->dsn))->create();
    $check($file instanceof FileMessageStore, 'CHAT_STORE=file must build the file store');
    $check($file->loadAll('bob') === [], 'the file store must not know bob');
    $agent($file, 'bob', $answer('I do not know your name.'))->chat(new UserMessage('What is my name?'));
    $check($listing() === ['chat.sqlite', 'neuron_bob.json'], 'storage: ' . implode(' ', $listing()));
    // Scene 11: a failed call is a NeuronException and stores nothing.
    $failing = $agent($sqlite(), 'cli', new FakeAIProvider());
    try {
        $failing->chat(new UserMessage('Hi'));
        throw new RuntimeException('a call with no answer did not fail');
    } catch (NeuronException) {
    }
    $check($sqlite()->loadAll('cli') === [], 'a failed call stored messages');
    // Scene 11: an unknown store is a RuntimeException with the message fail() prints.
    try {
        $s = StorageSettings::fromEnv(['CHAT_STORE' => 'redis'], $root);
        $redis = new MessageStoreFactory($s, new Database($s->dsn))->create();
        throw new RuntimeException('unknown store accepted');
    } catch (RuntimeException $e) {
        $check($e->getMessage() === 'Unknown CHAT_STORE redis', 'unknown store: ' . $e->getMessage());
    }
    // Scene 11: the thread rule and the lines the waits look for, in the assistant.php this part runs.
    $source = file_get_contents(__DIR__ . '/assistant.php');
    preg_match("~preg_match\('(/[^']+/)', \\\$thread\)~", $source, $rule);
    $check(isset($rule[1]), 'thread rule not found in assistant.php');
    foreach (['alice' => 1, 'bob' => 1, 'cli' => 1, 'Alice' => 0, '../etc' => 0] as $thread => $ok) {
        $check(preg_match($rule[1], $thread) === $ok, "thread rule wrong for {$thread}");
    }
    foreach ([
        '"Thread {$thread}, {$storage->chatStore} store."',
        '"Thread {$thread} cleared."',
        '"History: {$stored} stored, {$sent} sent to the model."',
        '"Model error: {$e->getMessage()}"',
        '"Configuration error: {$message}"',
        '"thread {$thread}: use lowercase letters, digits, dashes"',
        "\$argv[1] !== '-'",
    ] as $line) {
        $check(str_contains($source, $line), "assistant.php lacks {$line}");
    }
} finally {
    foreach (glob($root . '/storage/*') ?: [] as $f) {
        unlink($f);
    }
    is_dir($root . '/storage') && rmdir($root . '/storage');
    is_dir($root) && rmdir($root);
}
echo 'PERSISTENT CHAT OK' . PHP_EOL;
