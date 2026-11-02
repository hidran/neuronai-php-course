<?php
declare(strict_types=1);
// Offline check of the lesson (FakeAIProvider: no model call; a capturing
// transport: nothing is sent to Inspector). Usage: php verify-endpoint-traces.php <lab>
// <lab> is a Laravel lab with inspector-apm/inspector-laravel installed (the
// lesson's composer require). The app boots from a temporary mirror of the lab
// whose bootstrap/app.php is this folder's app.php, and the lesson's classes
// (the endpoint lab's AssistantAgent and AskController, this folder's
// AppServiceProvider) are loaded before the lab's: the check follows the files.
use App\Neuron\Agents\AssistantAgent;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Inspector\Configuration;
use Inspector\Models\Model;
use Inspector\Models\Segment;
use Inspector\Models\Transaction;
use Inspector\Transports\TransportInterface;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Testing\FakeAIProvider;
$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('lab path');
if (!is_dir($lab . '/vendor/inspector-apm/inspector-laravel')) {
    throw new RuntimeException(
        'The lab needs inspector-apm/inspector-laravel (the lesson\'s composer require).'
    );
}
// The Laravel package's own instance stays disabled: no real ingestion.
foreach (['INSPECTOR_ENABLE' => 'false', 'NEURON_AI_PROVIDER' => 'anthropic'] as $name => $value) {
    putenv("{$name}={$value}");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}
// 1. The facts the narration states about the installed packages.
$config = file_get_contents($lab . '/vendor/inspector-apm/inspector-laravel/config/inspector.php');
if (!str_contains($config, "env('INSPECTOR_API_KEY', env('INSPECTOR_INGESTION_KEY'))")) {
    throw new RuntimeException('inspector-laravel no longer reads INSPECTOR_INGESTION_KEY');
}
$subscriber = file_get_contents($lab . '/vendor/inspector-apm/inspector-php/src/Neuron/V4/InspectorSubscriber.php');
if (!str_contains($subscriber, 'protected Inspector $inspector,')
    || !str_contains($subscriber, 'new self(new Inspector($configuration))')) {
    throw new RuntimeException('InspectorSubscriber changed: re-check segments 1b and 5c');
}
// 2. A mirror of the lab with this folder's bootstrap/app.php.
$mirror = sys_get_temp_dir() . '/endpoint-traces-' . bin2hex(random_bytes(4));
mkdir($mirror . '/bootstrap', 0700, true);
foreach (scandir($lab) as $entry) {
    if (!in_array($entry, ['.', '..', 'bootstrap'], true)) {
        symlink($lab . '/' . $entry, $mirror . '/' . $entry);
    }
}
symlink($lab . '/bootstrap/cache', $mirror . '/bootstrap/cache');
symlink($lab . '/bootstrap/providers.php', $mirror . '/bootstrap/providers.php');
copy(__DIR__ . '/app.php', $mirror . '/bootstrap/app.php');
register_shutdown_function(static function () use ($mirror): void {
    foreach (scandir($mirror . '/bootstrap') as $entry) {
        if (!in_array($entry, ['.', '..'], true)) {
            unlink($mirror . '/bootstrap/' . $entry);
        }
    }
    rmdir($mirror . '/bootstrap');
    foreach (scandir($mirror) as $entry) {
        if (!in_array($entry, ['.', '..'], true)) {
            unlink($mirror . '/' . $entry);
        }
    }
    rmdir($mirror);
});
require $lab . '/vendor/autoload.php';
require __DIR__ . '/../../13-04-lab-first-endpoint/files/AssistantAgent.php';
require __DIR__ . '/../../13-04-lab-first-endpoint/files/AskController.php';
require __DIR__ . '/AppServiceProvider.php';
$app = require $mirror . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
// 3. The fake model, and an Inspector instance that only records.
$answer = 'Support is open Monday to Friday, 9:00 to 17:00 CET.';
$fake = new FakeAIProvider(new AssistantMessage($answer));
$app->instance(AIProviderInterface::class, $fake);
$transport = new class implements TransportInterface {
    /** @var list<Model> */
    public array $queue = [];
    /** @var list<Model> */
    public array $sent = [];
    public function addEntry(Model $model): TransportInterface
    {
        $this->queue[] = $model;
        return $this;
    }
    public function resetQueue(): TransportInterface
    {
        $this->queue = [];
        return $this;
    }
    public function flush(): TransportInterface
    {
        $this->sent = $this->queue;
        $this->queue = [];
        return $this;
    }
};
$inspector = new Inspector\Laravel\Inspector(new Configuration('offline-check'));
$inspector->setTransport($transport);
$app->instance('inspector', $inspector);
Facade::clearResolvedInstance('inspector');
$send = static function (array $body) use ($kernel, $inspector): Illuminate\Http\Response|Symfony\Component\HttpFoundation\Response {
    $request = Request::create('/api/ask', 'POST', server: [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
    ], content: json_encode($body, JSON_THROW_ON_ERROR));
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    $inspector->flush();
    return $response;
};
/** @return array{0: ?Transaction, 1: list<Segment>} */
$sent = static function () use ($transport): array {
    $transaction = array_find($transport->sent, fn (Model $m) => $m instanceof Transaction);
    $segments = array_values(array_filter($transport->sent, fn (Model $m) => $m instanceof Segment));
    return [$transaction, $segments];
};
// 4. One request: the transaction of the HTTP request holds the agent run and the LLM call.
$response = $send(['message' => 'When is your support desk open?']);
if ($response->getStatusCode() !== 200
    || json_decode((string) $response->getContent(), true)['answer'] !== $answer) {
    throw new RuntimeException('POST /api/ask did not return the fake answer');
}
[$transaction, $segments] = $sent();
if ($transaction?->name !== 'POST /api/ask' || $transaction->type !== 'request'
    || $transaction->result !== '200') {
    throw new RuntimeException('no request transaction named POST /api/ask with result 200');
}
$workflow = array_find($segments, fn (Segment $s) => $s->type === 'agent.workflow');
$inference = array_find($segments, fn (Segment $s) => $s->type === 'agent.inference');
if ($workflow?->label !== 'AssistantAgent' || $inference === null
    || !str_starts_with((string) $inference->label, 'inference(')) {
    throw new RuntimeException('the agent segments are missing from the request transaction');
}
if (count($fake->getRecorded()) !== 1) {
    throw new RuntimeException('expected exactly one model call');
}
// 5. Validation fails before the agent: a 422 transaction with no agent segment.
$response = $send([]);
[$transaction, $segments] = $sent();
if ($response->getStatusCode() !== 422 || $transaction?->result !== '422'
    || array_find($segments, fn (Segment $s) => str_starts_with($s->type, 'agent.')) !== null
    || count($fake->getRecorded()) !== 1) {
    throw new RuntimeException('an invalid request reached the agent or was not traced');
}
// 6. An agent built by hand never passes through the container: no subscriber, no segments.
$inspector->startTransaction('hand-built agent');
$own = new FakeAIProvider(new AssistantMessage('ok'));
AssistantAgent::make($own)->for('hand-built')->chat(new UserMessage('Hi'));
$inspector->flush();
[, $segments] = $sent();
if (count($own->getRecorded()) !== 1
    || array_find($segments, fn (Segment $s) => str_starts_with($s->type, 'agent.')) !== null) {
    throw new RuntimeException('a hand-built agent was traced without a subscriber');
}
echo 'ENDPOINT TRACES OK' . PHP_EOL;
