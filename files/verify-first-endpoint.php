<?php
declare(strict_types=1);
// Offline check of "Lab: the first Laravel response" (FakeAIProvider: no model call, no keys).
// Usage: php verify-first-endpoint.php <lab>
// <lab> is the Laravel lab at the end of the lesson: install:api, routes/api.php, AskController,
// AssistantAgent with the support instructions, requests/ask.http, tests/Feature/AskEndpointTest.php,
// all equal to this folder's files. Requests go through Laravel's HTTP kernel, no server is started.
use App\Neuron\Agents\AssistantAgent;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('usage: verify-first-endpoint.php <lab>');
$fail = static function (string $what): never {
    throw new RuntimeException($what);
};
$same = static fn (string $a, string $b): bool => rtrim((string) file_get_contents($a), "\n") === rtrim((string) file_get_contents($b), "\n");
// 1. The lab holds the lesson's files.
foreach ([
    'routes/api.php' => 'api.php',
    'bootstrap/app.php' => 'app.php',
    'app/Http/Controllers/AskController.php' => 'AskController.php',
    'app/Neuron/Agents/AssistantAgent.php' => 'AssistantAgent.support.generated.php',
    'requests/ask.http' => 'ask.http',
    'tests/Feature/AskEndpointTest.php' => 'AskEndpointTest.php',
] as $path => $file) {
    if (!is_file("{$lab}/{$path}") || !$same("{$lab}/{$path}", __DIR__ . "/{$file}")) {
        $fail("{$path} differs from files/{$file}");
    }
}
// 2. Boot the application; the route is POST api/ask, named ask, prefixed once.
$baseEnv = getenv();
require $lab . '/vendor/autoload.php';
$app = require $lab . '/bootstrap/app.php';
$kernel = $app->make(HttpKernel::class);
$kernel->bootstrap();
$route = Route::getRoutes()->getByName('ask');
if ($route === null || $route->uri() !== 'api/ask' || $route->methods() !== ['POST']) {
    $fail('the ask route is not POST api/ask');
}
if ($route->getActionName() !== 'App\\Http\\Controllers\\AskController@__invoke') {
    $fail('the ask route does not call AskController::__invoke');
}
// 3. A recording agent behind the injected class: every request gets its own ask_ thread.
final class RecordingAssistantAgent extends AssistantAgent
{
    /** @var list<string> */
    public static array $threads = [];
    #[\Override]
    public function for(string $workflowId): static
    {
        self::$threads[] = $workflowId;
        return parent::for($workflowId);
    }
}
$fake = new FakeAIProvider(
    new AssistantMessage('Support is open Monday to Friday, 9:00 to 17:00 CET.'),
    new AssistantMessage('This is the first message I have received from you.'),
    new AssistantMessage(''),
);
$app->instance(AssistantAgent::class, RecordingAssistantAgent::make()->setAiProvider($fake));
$send = static function (array|string $body) use ($kernel): array {
    $json = is_string($body) ? $body : json_encode($body, JSON_THROW_ON_ERROR);
    $request = Request::create('/api/ask', 'POST', server: [
        'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json',
    ], content: $json);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    return [$response->getStatusCode(), json_decode((string) $response->getContent(), true)];
};
// 4. The valid request: 200 and the agent's text under answer.
[$status, $body] = $send(['message' => 'When is your support desk open?']);
if ($status !== 200 || $body !== ['answer' => 'Support is open Monday to Friday, 9:00 to 17:00 CET.']) {
    $fail("valid request: {$status} " . json_encode($body));
}
// The product instructions reached the provider as the system prompt.
$first = $fake->getRecorded()[0];
if (!str_contains((string) $first->systemPrompt?->getContent(), 'Support: Monday to Friday, 9:00 to 17:00 CET.')) {
    $fail('the support instructions were not sent as the system prompt');
}
// 5. The invalid requests: 422, the field error, no model call.
[$status, $body] = $send([]);
if ($status !== 422 || ($body['errors']['message'][0] ?? '') !== 'The message field is required.') {
    $fail("missing message: {$status} " . json_encode($body));
}
[$status, $body] = $send(['message' => 42]);
if ($status !== 422 || ($body['errors']['message'][0] ?? '') !== 'The message field must be a string.') {
    $fail("wrong type: {$status} " . json_encode($body));
}
[$status] = $send(['message' => str_repeat('a', 2001)]);
if ($status !== 422 || $fake->getCallCount() !== 1) {
    $fail('an invalid request reached the model');
}
// 6. The follow-up is a new request: a new thread, and only its own question reaches the model.
[$status, $body] = $send(['message' => 'What did I just ask you?']);
$second = $fake->getRecorded()[1];
$users = array_values(array_filter($second->messages, static fn ($m): bool => $m instanceof UserMessage));
if ($status !== 200 || count($users) !== 1 || $users[0]->getContent() !== 'What did I just ask you?') {
    $fail('the follow-up carried earlier messages');
}
$threads = RecordingAssistantAgent::$threads;
if (count($threads) !== 2 || $threads[0] === $threads[1] || !str_starts_with($threads[0], 'ask_') || !str_starts_with($threads[1], 'ask_')) {
    $fail('each request needs its own ask_ thread: ' . implode(', ', $threads));
}
// 7. No answer text: a deliberate 502 error, not an empty answer.
[$status, $body] = $send(['message' => 'Anything?']);
if ($status !== 502 || $body !== ['error' => 'The agent returned no answer.']) {
    $fail("empty answer: {$status} " . json_encode($body));
}
// 8. The lesson's feature test passes (its own process, with the human-readable output the lesson shows:
//    PAO_DISABLE keeps laravel/pao from switching to its JSON output when an AI agent runs this check).
$proc = proc_open([PHP_BINARY, 'artisan', 'test', '--filter=AskEndpointTest', '--no-ansi'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $lab, ['PAO_DISABLE' => '1'] + $baseEnv);
$out = (string) preg_replace('/\e\[[0-9;]*m/', '', stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]));
if (proc_close($proc) !== 0 || preg_match('/Tests:\s+2 passed/', $out) !== 1) {
    $fail("php artisan test --filter=AskEndpointTest:\n{$out}");
}
echo "FIRST ENDPOINT OK\n";
