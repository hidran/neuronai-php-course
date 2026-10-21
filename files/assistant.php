<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\PackageVersionTool;
use Dotenv\Dotenv;
use NeuronAI\Chat\History\FileMessageStore;
use NeuronAI\Chat\History\SQLMessageStore;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(__DIR__)->safeLoad();
function env(string $name): ?string
{
    $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
    return $value === false || $value === '' ? null : $value;
}
function fail(string $message): never
{
    fwrite(STDERR, "Configuration error: {$message}" . PHP_EOL);
    exit(1);
}
function sqlStore(string $storage): SQLMessageStore
{
    is_dir($storage) || mkdir($storage, recursive: true);
    $pdo = new PDO("sqlite:{$storage}/chat.sqlite");
    $pdo->exec('CREATE TABLE IF NOT EXISTS chat_messages ('
        . 'id INTEGER PRIMARY KEY AUTOINCREMENT,'
        . ' thread_id VARCHAR(255) NOT NULL,'
        . ' message_id VARCHAR(64) NOT NULL,'
        . ' role VARCHAR(32) NOT NULL, content TEXT, meta TEXT,'
        . ' archived_at TIMESTAMP,'
        . ' created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,'
        . ' updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,'
        . ' UNIQUE (thread_id, message_id))');
    return new SQLMessageStore(pdo: $pdo);
}
$keys = [
    'anthropic' => 'ANTHROPIC_KEY',
    'openai' => 'OPENAI_KEY',
    'gemini' => 'GEMINI_KEY',
    'ollama' => null,
];
$provider = env('NEURON_PROVIDER') ?? 'ollama';
if (!array_key_exists($provider, $keys)) {
    $known = implode(', ', array_keys($keys));
    fail("unknown provider {$provider}, use one of: {$known}");
}
$key = $keys[$provider];
if ($key !== null && env($key) === null) {
    fail("{$key} is not set, and {$provider} needs it");
}
$stores = ['file', 'sqlite'];
$storeName = env('ASSISTANT_STORE') ?? 'file';
if (!in_array($storeName, $stores, true)) {
    $known = implode(', ', $stores);
    fail("unknown store {$storeName}, use one of: {$known}");
}
$thread = env('ASSISTANT_THREAD') ?? 'cli';
if (preg_match('/^[a-z0-9-]{1,64}$/', $thread) !== 1) {
    fail("thread {$thread}: use lowercase letters, digits, dashes");
}
$storage = __DIR__ . '/storage/assistant';
$store = match ($storeName) {
    'file' => new FileMessageStore(directory: $storage, ext: '.json'),
    'sqlite' => sqlStore($storage),
};
$agent = AssistantAgent::make()
    ->useProvider($provider)
    ->addTool(new PackageVersionTool())
    ->setMessageStore($store)
    ->setThreadId($thread);
function ask(AssistantAgent $agent, string $question): void
{
    $state = $agent->chat(new UserMessage($question));
    echo $state->getMessage()?->getContent() . PHP_EOL;
    $engine = $agent->getProvider();
    echo 'Answered by: ' . $engine::class
        . ", {$engine->getModel()}" . PHP_EOL;
}
if (isset($argv[1])) {
    ask($agent, $argv[1]);
    exit(0);
}
echo "Chatting on {$provider}. Type exit to quit." . PHP_EOL;
echo "Thread {$thread}, {$storeName} store."
    . ' Commands: /history, /reset.' . PHP_EOL;
echo 'You: ';
while (($line = fgets(STDIN)) !== false) {
    $question = trim($line);
    if ($question === 'exit') {
        break;
    }
    if ($question === '/reset') {
        $agent->resetConversation();
        echo "Thread {$thread} cleared." . PHP_EOL;
    } elseif ($question === '/history') {
        $stored = count($store->loadAll($thread));
        $sent = count($agent->getChatHistory()->getMessages());
        echo "History: {$stored} stored, {$sent} sent to the model."
            . PHP_EOL;
    } elseif ($question !== '') {
        try {
            ask($agent, $question);
        } catch (Throwable $e) {
            fwrite(STDERR, 'Model error: ' . $e->getMessage()
                . PHP_EOL);
        }
    }
    echo 'You: ';
}
echo 'Bye.' . PHP_EOL;
