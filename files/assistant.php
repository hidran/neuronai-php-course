<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\PackageVersionTool;
use Dotenv\Dotenv;
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
$agent = AssistantAgent::make()
    ->useProvider($provider)
    ->addTool(new PackageVersionTool())
    ->setThreadId('cli');
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
echo 'You: ';
while (($line = fgets(STDIN)) !== false) {
    $question = trim($line);
    if ($question === 'exit') {
        break;
    }
    if ($question !== '') {
        ask($agent, $question);
    }
    echo 'You: ';
}
echo 'Bye.' . PHP_EOL;
