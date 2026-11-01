<?php
declare(strict_types=1);
// Offline check of "A Laravel project and the NeuronAI SDK" (no model call, no network).
// Usage: php verify-laravel-sdk.php <lab>
// <lab> is the Laravel application at the end of the lesson: composer create-project
// laravel/laravel (13.x), "php": ">=8.5", neuron-core/neuron-laravel ^2.0 and
// neuron-core/neuron-ai ^4.1 required. Every fact the narration states about the SDK
// is checked against the installed packages.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\Laravel\AIProviderManager;
use NeuronAI\Laravel\Neuron;
use NeuronAI\Laravel\NeuronAIServiceProvider;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Testing\FakeAIProvider;
$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('usage: verify-laravel-sdk.php <lab>');
$fail = static function (string $what): never {
    throw new RuntimeException($what);
};
// 1. composer.json: our runtime and both release lines, declared by the application.
$composer = json_decode((string) file_get_contents($lab . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
$require = $composer['require'] ?? [];
if (($require['php'] ?? '') !== '>=8.5') {
    $fail('composer.json must require "php": ">=8.5"');
}
if (($require['neuron-core/neuron-laravel'] ?? '') !== '^2.0' || ($require['neuron-core/neuron-ai'] ?? '') !== '^4.1') {
    $fail('composer.json must require neuron-core/neuron-laravel ^2.0 and neuron-core/neuron-ai ^4.1');
}
if (!str_starts_with($require['laravel/framework'] ?? '', '^13.')) {
    $fail('the application is not on the Laravel 13 line');
}
if (!is_file($lab . '/composer.lock')) {
    $fail('composer.lock is missing: the resolved releases are not recorded');
}
// 2. The application boots and package discovery registered the SDK's service provider.
require $lab . '/vendor/autoload.php';
$app = require $lab . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if ($app->getProviders(NeuronAIServiceProvider::class) === []) {
    $fail('NeuronAIServiceProvider is not registered (package discovery)');
}
if (!str_starts_with($app->version(), '13.')) {
    $fail('Laravel ' . $app->version() . ' is not 13.x');
}
// 3. The six generators: agent, retrieval agent (rag), tool, workflow, node, middleware.
$neuron = array_values(array_filter(array_keys(Artisan::all()), static fn (string $name): bool => str_starts_with($name, 'neuron:')));
sort($neuron);
$expected = ['neuron:agent', 'neuron:middleware', 'neuron:node', 'neuron:rag', 'neuron:tool', 'neuron:workflow'];
if ($neuron !== $expected) {
    $fail('neuron commands: ' . implode(', ', $neuron));
}
// 4. The configuration publishing tag and the managers.
$published = ServiceProvider::pathsToPublish(NeuronAIServiceProvider::class, 'neuron-config');
if (count($published) !== 1 || !str_ends_with((string) array_key_first($published), 'config/neuron.php')) {
    $fail('the neuron-config tag does not publish config/neuron.php');
}
if (!$app->isShared(AIProviderManager::class)) {
    $fail('AIProviderManager is not a singleton');
}
// 5. The provider manager builds the core provider classes (no request is sent).
config()->set('neuron.provider.anthropic.key', 'offline-check');
$anthropic = $app->make(AIProviderManager::class)->driver('anthropic');
if (!$anthropic instanceof Anthropic) {
    $fail('the anthropic driver is not the core Anthropic provider');
}
// 6. The agent stub extends the core Agent class.
$sdk = $lab . '/vendor/neuron-core/neuron-laravel';
$agentStub = (string) file_get_contents($sdk . '/src/Commands/stubs/agent.stub');
if (!str_contains($agentStub, 'use NeuronAI\Agent\Agent;') || !str_contains($agentStub, 'extends Agent')) {
    $fail('agent.stub no longer extends NeuronAI\Agent\Agent');
}
// 7. The convenience facade's agent has no thread: chat() fails before any model call.
$fake = new FakeAIProvider(new AssistantMessage('never sent'));
try {
    new Neuron($fake)->chat(new UserMessage('Hello'));
    $fail('Neuron::chat() ran without a thread ID: re-check segment 5b');
} catch (AgentException $e) {
    if (!str_contains($e->getMessage(), 'no thread ID')) {
        throw $e;
    }
}
if ($fake->getCallCount() !== 0) {
    $fail('the facade reached the provider');
}
// 8. The node scaffold imports two event classes that do not exist in the installed core.
$nodeStub = (string) file_get_contents($sdk . '/src/Commands/stubs/node.stub');
foreach (['StartEvent', 'StopEvent'] as $event) {
    if (!str_contains($nodeStub, "use NeuronAI\\Workflow\\{$event};")) {
        $fail("node.stub no longer imports NeuronAI\\Workflow\\{$event}: re-check segment 5b");
    }
    if (class_exists("NeuronAI\\Workflow\\{$event}") || !class_exists("NeuronAI\\Workflow\\Events\\{$event}")) {
        $fail("the core moved {$event}: re-check segment 5b");
    }
}
// 9. The published message table has no message_id column; the core message store needs it.
$migration = (string) file_get_contents($sdk . '/database/migrations/2025_12_23_144301_create_chat_messages_table.php');
$later = implode('', array_map('file_get_contents', glob($sdk . '/database/migrations/*.php') ?: []));
if (str_contains($later, 'message_id') || !str_contains($migration, "create('chat_messages'")) {
    $fail('the SDK migrations now define message_id: re-check segment 5b');
}
$store = (string) file_get_contents($lab . '/vendor/neuron-core/neuron-ai/src/Chat/History/EloquentMessageStore.php');
if (!str_contains($store, "'message_id'")) {
    $fail('EloquentMessageStore no longer uses message_id');
}
echo "LARAVEL SDK OK\n";
