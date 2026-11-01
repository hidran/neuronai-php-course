<?php
declare(strict_types=1);
// Offline check of "EloquentMessageStore and application-owned tables"
// (FakeAIProvider: no model call; an in-memory SQLite database: the lab's
// database file is never touched).
// Usage: php verify-eloquent-message-store.php <lab>
// <lab> is the Laravel lab built by this lesson's prepare.sh, at the start or at
// the end of the lesson: the lab's own migrations run except any
// *_create_chat_messages_table.php, which is replaced by this folder's
// migration; this folder's SupportAgent and the container lesson's
// NeuronServiceProvider are loaded before the lab's own.
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Neuron\Agents\SupportAgent;
use App\Providers\NeuronServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\History\EloquentMessageStore;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('lab path');
foreach ([
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_URL' => '',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
] as $name => $value) {
    putenv("{$name}={$value}");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}
require $lab . '/vendor/autoload.php';
require __DIR__ . '/SupportAgent.php';
require dirname(__DIR__, 2) . '/13-09-agents-in-container/files/NeuronServiceProvider.php';
$app = require $lab . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
// Laravel's handler would render a failure and exit 0: report it and exit 1.
set_exception_handler(static function (Throwable $e): never {
    fwrite(STDERR, 'VERIFY FAILED: ' . $e->getMessage() . PHP_EOL);
    exit(1);
});
$app->register(NeuronServiceProvider::class);
$app->instance('request', Request::create('/'));
config(['support' => require __DIR__ . '/start/support.php']);
$fail = static fn (string $why) => throw new RuntimeException($why);
// 1. What the narration says about the installed packages.
$sdk = $lab . '/vendor/neuron-core/neuron-laravel';
$sdkMigration = file_get_contents($sdk . '/database/migrations/2025_12_23_144301_create_chat_messages_table.php');
$sdkModel = file_get_contents($sdk . '/src/Models/ChatMessage.php');
if (str_contains($sdkMigration, 'message_id') || str_contains($sdkModel, 'message_id')) {
    $fail('the SDK migration or model now has message_id: re-check segment 2b');
}
$columns = (new ReflectionClassConstant(EloquentMessageStore::class, 'COLUMNS'))->getValue();
if ($columns !== ['message_id', 'role', 'content', 'meta']) {
    $fail('EloquentMessageStore::COLUMNS changed: ' . implode(', ', $columns));
}
if (!method_exists(Agent::class, 'contextWindow') || method_exists(Agent::class, 'chatHistory')) {
    $fail('the contextWindow() hook is missing or a chatHistory() hook is back');
}
// 2. The lab's migrations, with this folder's chat_messages migration.
$paths = array_values(array_filter(
    glob($lab . '/database/migrations/*.php'),
    fn (string $p) => !str_ends_with($p, '_create_chat_messages_table.php'),
));
$paths[] = __DIR__ . '/create_chat_messages_table.php';
Artisan::call('migrate', ['--force' => true, '--realpath' => true, '--path' => $paths]);
$indexes = Schema::getIndexes('chat_messages');
$unique = array_find($indexes, fn (array $i) => $i['unique'] && !$i['primary']);
if (($unique['columns'] ?? null) !== ['thread_id', 'message_id']) {
    $fail('no unique index over thread_id and message_id');
}
$expected = ['id', 'thread_id', 'message_id', 'role', 'content', 'meta',
    'archived_at', 'created_at', 'updated_at'];
if (Schema::getColumnListing('chat_messages') !== $expected) {
    $fail('chat_messages columns: ' . implode(', ', Schema::getColumnListing('chat_messages')));
}
$primary = array_find(Schema::getIndexes('workflow_store'), fn (array $i) => $i['primary']);
if (($primary['columns'] ?? null) !== ['partition', 'key']
    || Schema::getColumnListing('workflow_store') !== ['partition', 'key', 'value', 'updated_at']) {
    $fail('workflow_store is not (partition, key, value, updated_at) keyed on partition and key');
}
// 3. On the MySQL family the identifiers become VARBINARY with their lengths
//    (pretend mode on a MySQL connection that never connects).
$mysql = new MySqlConnection(
    static fn () => throw new RuntimeException('no MySQL connection in the check'),
    'verify',
    '',
    ['driver' => 'mysql', 'name' => 'verify'],
);
$app['db']->extend('verify-mysql', fn () => $mysql);
config(['database.connections.verify-mysql' => ['driver' => 'mysql']]);
$default = DB::getDefaultConnection();
DB::setDefaultConnection('verify-mysql');
$migration = require __DIR__ . '/create_chat_messages_table.php';
$sql = implode("\n", array_column($mysql->pretend(fn () => $migration->up()), 'query'));
DB::setDefaultConnection($default);
foreach (['`thread_id` varbinary(255)', '`message_id` varbinary(255)',
    '`partition` varbinary(510)', '`key` varbinary(510)'] as $column) {
    if (!str_contains($sql, $column)) {
        $fail("MySQL DDL lacks {$column}");
    }
}
// 4. The store: one row for a repeated append, threads apart, archive keeps the transcript.
$store = new EloquentMessageStore(ChatMessage::class);
$message = new UserMessage('Where is my invoice?');
$store->append('demo-thread-a', $message);
$store->append('demo-thread-a', $message);
$loaded = $store->loadAll('demo-thread-a');
if (count($loaded) !== 1 || $loaded[0]->getId() !== $message->getId()
    || ChatMessage::query()->count() !== 1) {
    $fail('a repeated append did not keep one row with the same message id');
}
$theirs = new UserMessage('Another customer');
$store->append('demo-thread-b', $theirs);
$ids = fn (array $messages) => array_map(fn (Message $m) => $m->getId(), $messages);
if ($ids($store->loadAll('demo-thread-a')) !== [$message->getId()]
    || $ids($store->loadAll('demo-thread-b')) !== [$theirs->getId()]) {
    $fail('the two threads are not separate');
}
$store->archive('demo-thread-a', 1);
if ($store->loadActive('demo-thread-a') !== [] || count($store->loadAll('demo-thread-a')) !== 1) {
    $fail('archive did not separate active history from the transcript');
}
// 5. The agent: the hook's budget, and a fresh agent sends the stored question.
$budget = (new ReflectionMethod(SupportAgent::class, 'contextWindow'))->invoke(app(SupportAgent::class));
if ($budget !== 32000 || app(MessageStoreInterface::class) !== app(MessageStoreInterface::class)) {
    $fail("contextWindow() returned {$budget}");
}
$thread = Conversation::factory()->create()->threadId();
$ask = static function (Agent $agent, string $answer, string $question) use ($thread): FakeAIProvider {
    $fake = new FakeAIProvider(new AssistantMessage($answer));
    $agent->setAiProvider($fake)->for($thread)->chat(new UserMessage($question));
    return $fake;
};
$texts = fn (FakeAIProvider $f) => array_map(fn (Message $m) => $m->getContent(), $f->getRecorded()[0]->messages);
$ask(app(SupportAgent::class), 'Noted.', 'My order number is 4417.');
$second = $ask(app(SupportAgent::class), 'It is 4417.', 'What was my order number?');
if (!in_array('My order number is 4417.', $texts($second), true)) {
    $fail('a fresh agent did not send the stored question');
}
// 6. The pitfall of segment 7b: an old chatHistory() method is never called,
//    so a fresh agent of that class starts from the default in-memory store.
$old = static fn () => new class () extends Agent {
    protected function chatHistory(): MessageStoreInterface
    {
        return app(MessageStoreInterface::class);
    }
};
$ask($old(), 'Noted.', 'My name is Ada.');
if (in_array('My name is Ada.', $texts($ask($old(), 'Hello.', 'Who am I?')), true)) {
    $fail('a chatHistory() method reached the store: re-check segment 7b');
}
echo 'ELOQUENT MESSAGE STORE OK' . PHP_EOL;
