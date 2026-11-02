<?php
declare(strict_types=1);
// Offline check of "Agents in the container" (FakeAIProvider: no model call; an
// in-memory SQLite database: the lab's database file is never touched).
// Usage: php verify-agents-in-container.php <lab>
// <lab> is the Laravel lab built by this lesson's prepare.sh, at the start or at
// the end of the lesson: this folder's SupportAgent, NeuronServiceProvider and
// routes/console.php are loaded before the lab's own, so the check follows the
// lesson's files.
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Neuron\Agents\SupportAgent;
use App\Providers\NeuronServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\History\EloquentMessageStore;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Workflow\Persistence\DatabasePersistence;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Workflow\Persistence\PersistenceInterface;
use NeuronAI\Workflow\Workflow;
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
require __DIR__ . '/NeuronServiceProvider.php';
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
// The lesson's routes/console.php, read before Artisan starts.
require __DIR__ . '/console.php';
Artisan::call('migrate', ['--force' => true]);
$fail = static fn (string $why) => throw new RuntimeException($why);
// The fake answers every agent the container builds from here on: the
// AIProviderInterface binding of AppServiceProvider is replaced, as the
// feature test does (no keys, no model call).
$answer = 'Our desk opens at nine.';
$fake = new FakeAIProvider(new AssistantMessage($answer));
$app->instance(AIProviderInterface::class, $fake);
// 1. The constructor: the provider as a plain parameter, then two promoted
//    dependencies named apart from the base properties; no provider() hook.
$constructor = new ReflectionMethod(SupportAgent::class, '__construct');
$params = array_map(
    fn (ReflectionParameter $p) => [$p->getName(), (string) $p->getType(), $p->isPromoted()],
    $constructor->getParameters(),
);
if ($params !== [
    ['provider', AIProviderInterface::class, false],
    ['conversations', MessageStoreInterface::class, true],
    ['runs', PersistenceInterface::class, true],
]) {
    $fail('SupportAgent::__construct() is not (provider, conversations, runs)');
}
if (!new ReflectionClass(SupportAgent::class)->isFinal()) {
    $fail('SupportAgent is not final');
}
foreach (['messageStore' => Agent::class, 'persistence' => Workflow::class] as $prop => $base) {
    $declared = new ReflectionProperty(SupportAgent::class, $prop);
    if (!is_a($declared->getDeclaringClass()->getName(), $base, true)
        || $declared->getDeclaringClass()->getName() === SupportAgent::class) {
        $fail("SupportAgent redeclares \${$prop}");
    }
}
foreach (['messageStore', 'persistence', 'instructions'] as $hook) {
    $method = new ReflectionMethod(SupportAgent::class, $hook);
    if ($method->getDeclaringClass()->getName() !== SupportAgent::class
        || $method->getAttributes(Override::class) === []) {
        $fail("SupportAgent does not override {$hook}() with #[\\Override]");
    }
}
if (new ReflectionMethod(SupportAgent::class, 'provider')->getDeclaringClass()->getName() === SupportAgent::class) {
    $fail('SupportAgent still overrides provider()');
}
// Our provider: final, strict types, #[\Override] on register() only (the
// parent ServiceProvider has no boot() to override).
$provider = new ReflectionClass(NeuronServiceProvider::class);
if (!$provider->isFinal()
    || new ReflectionMethod(NeuronServiceProvider::class, 'register')->getAttributes(Override::class) === []
    || method_exists(ServiceProvider::class, 'boot')
    || !str_contains((string) file_get_contents((string) $provider->getFileName()), 'declare(strict_types=1);')) {
    $fail('NeuronServiceProvider is not final with strict types and #[\Override] on register()');
}
// 2. make() forwards its arguments: a workflow identifier no longer fits.
try {
    SupportAgent::make(workflowId: 'thread-1');
    $fail('make(workflowId:) still builds a SupportAgent');
} catch (Error $e) {
    if (!str_contains($e->getMessage(), 'workflowId')) {
        throw $e;
    }
}
// 3. Lifetimes: one shared store, a fresh persistence and a fresh agent that
//    holds the injected provider; for() returns a copy and leaves it unbound.
$store = app(MessageStoreInterface::class);
if (!$store instanceof EloquentMessageStore || $store !== app(MessageStoreInterface::class)) {
    $fail('MessageStoreInterface is not a shared EloquentMessageStore');
}
$persistence = app(PersistenceInterface::class);
if (!$persistence instanceof DatabasePersistence
    || $persistence === app(PersistenceInterface::class)) {
    $fail('PersistenceInterface is not a fresh DatabasePersistence');
}
$agent = app(SupportAgent::class);
if ($agent === app(SupportAgent::class)) {
    $fail('SupportAgent is shared');
}
if ($agent->getProvider() !== $fake) {
    $fail('the container did not inject the bound provider');
}
$copy = $agent->for('thread-1');
if ($copy === $agent || $agent->getThreadId() !== null || $copy->getProvider() !== $fake) {
    $fail('for() did not return a copy that shares the provider');
}
Artisan::call('agent:lifetimes');
$lines = array_values(array_filter(array_map('trim', explode("\n", Artisan::output()))));
if ($lines !== [
    'EloquentMessageStore: shared',
    'DatabasePersistence: fresh',
    'SupportAgent: fresh',
]) {
    $fail('agent:lifetimes printed: ' . implode(' | ', $lines));
}
// 4. The action: the owner gets the fake answer with one call, the
//    conversation lands in chat_messages, a stranger is rejected before the agent.
$conversation = Conversation::factory()->create();
$send = static function (User $user) use ($kernel, $conversation) {
    Auth::forgetGuards();
    Auth::guard('web')->setUser($user);
    $request = Request::create(
        "/api/conversations/{$conversation->id}/messages",
        'POST',
        server: ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
        content: json_encode(['message' => 'When do you open?'], JSON_THROW_ON_ERROR),
    );
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    return $response;
};
$response = $send($conversation->user);
if ($response->getStatusCode() !== 200
    || json_decode((string) $response->getContent(), true) !== ['answer' => $answer]
    || $fake->getCallCount() !== 1) {
    $fail('the owner did not get the fake answer with one call: ' . $response->getStatusCode());
}
$rows = ChatMessage::query()->where('thread_id', $conversation->threadId())->count();
if ($rows !== 2) {
    $fail("expected 2 chat_messages rows for the thread, found {$rows}");
}
$response = $send(User::factory()->create());
if ($response->getStatusCode() !== 403 || $fake->getCallCount() !== 1) {
    $fail('a stranger was not rejected before the agent: ' . $response->getStatusCode());
}
echo 'AGENTS IN CONTAINER OK' . PHP_EOL;
