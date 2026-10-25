<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\Config\StorageSettings;
use App\PackageVersionTool;
use App\Support\Database;
use App\Support\MessageStoreFactory;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\NeuronException;
Dotenv::createImmutable(__DIR__)->safeLoad();
function fail(string $message): never
{
    fwrite(STDERR, "Configuration error: {$message}" . PHP_EOL);
    exit(1);
}
try {
    $settings = Settings::fromEnv($_ENV);
    $storage = StorageSettings::fromEnv($_ENV, __DIR__);
    $database = new Database(
        $storage->dsn, $storage->user, $storage->password,
    );
    $store = new MessageStoreFactory($storage, $database)->create();
} catch (RuntimeException $e) {
    fail($e->getMessage());
}
$thread = $argv[2] ?? 'cli';
if (preg_match('/^[a-z0-9-]{1,64}$/', $thread) !== 1) {
    fail("thread {$thread}: use lowercase letters, digits, dashes");
}
$agent = AssistantAgent::make(new ProviderFactory($settings)->create())
    ->addTool(new PackageVersionTool())
    ->setMessageStore($store)
    ->setThreadId($thread);
function ask(AssistantAgent $agent, Settings $settings, string $q): void
{
    try {
        $state = $agent->chat(new UserMessage($q));
    } catch (NeuronException $e) {
        fwrite(STDERR, "Model error: {$e->getMessage()}" . PHP_EOL);
        return;
    }
    echo $state->getMessage()?->getContent() . PHP_EOL;
    echo "Answered by: {$settings->provider->value}, "
        . $settings->model . PHP_EOL;
}
if (isset($argv[1]) && $argv[1] !== '-') {
    ask($agent, $settings, $argv[1]);
    exit(0);
}
echo "Chatting on {$settings->provider->value}. Type exit to quit."
    . PHP_EOL;
echo "Thread {$thread}, {$storage->chatStore} store."
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
        ask($agent, $settings, $question);
    }
    echo 'You: ';
}
echo 'Bye.' . PHP_EOL;
