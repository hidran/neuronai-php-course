<?php

declare(strict_types=1);
$lab = $argv[1] ?? dirname(__DIR__);
// Check of the exercise "a limit on messages from the page".
// Usage: php exercise-message-limit-check.php <your project>
// It runs the project's OWN providers, routes and views (nothing of the
// lesson is loaded over them) on an in-memory SQLite database and an array
// cache, through the HTTP kernel with a cookie jar, posting JSON the way the
// page script does. A FakeAIProvider is bound: it holds one answer, for the
// message sent after the minute is over. No model is called.
// It ends with "MESSAGE LIMIT EXERCISE OK" when the acceptance list holds.

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Testing\FakeAIProvider;
use Symfony\Component\HttpFoundation\Response;

$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('project path');
foreach ([
    'APP_ENV' => 'local',
    'APP_DEBUG' => 'false',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_URL' => '',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'database',
    'QUEUE_CONNECTION' => 'sync',
    'LOG_CHANNEL' => 'null',
    'BCRYPT_ROUNDS' => '4',
] as $name => $value) {
    putenv("{$name}={$value}");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}
require $lab.'/vendor/autoload.php';
$app = require $lab.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
set_exception_handler(static function (Throwable $e): never {
    fwrite(STDERR, 'EXERCISE CHECK FAILED: '.$e->getMessage().PHP_EOL);
    exit(1);
});
$fail = static fn (string $why) => throw new RuntimeException($why);
$compiled = sys_get_temp_dir().'/exercise-message-limit-'.bin2hex(random_bytes(6));
mkdir($compiled, 0700, true);
config(['view.compiled' => $compiled]);
register_shutdown_function(static function () use ($compiled): void {
    array_map('unlink', glob("{$compiled}/*") ?: []);
    rmdir($compiled);
});
$fake = new FakeAIProvider(new AssistantMessage('Noted.'));
$app->instance(AIProviderInterface::class, $fake);
// 1. What must stay as the lesson left it (compared without whitespace).
$flat = static fn (string $text): string => preg_replace('/\s+/', ' ', trim($text));
foreach ([
    'app/Http/Controllers/ConversationMessageController.php' => 'e5666bcefa70b44727a8546b98f510ccdda0f021',
    'routes/api.php' => '646758d930805f37ebf030b6f42eda901cbccff5',
] as $file => $sha) {
    if (sha1($flat((string) @file_get_contents("{$lab}/{$file}"))) !== $sha) {
        $fail("{$file} changed: the exercise does not touch it");
    }
}
// 2. The routes: throttle:chat on the web route only.
$web = Route::getRoutes()->getByName('conversations.messages.web') ?? $fail('no route conversations.messages.web');
$api = Route::getRoutes()->getByName('conversations.messages') ?? $fail('no route conversations.messages');
if (array_values(array_filter($web->gatherMiddleware(), 'is_string')) !== ['web', 'auth', 'throttle:chat']) {
    $fail('the web message route does not run web, auth, then throttle:chat');
}
if (array_filter($api->gatherMiddleware(), fn ($m) => is_string($m) && str_starts_with($m, 'throttle')) !== []
    || $api->gatherMiddleware() !== ['api', 'auth:sanctum']) {
    $fail('the API message route changed: it keeps api and auth:sanctum only');
}
// 3. The limiter: one limit a minute, counted by the signed-in user.
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--force' => true]);
$marco = User::query()->where('name', 'Marco')->sole();
$nora = User::query()->where('name', 'Nora')->sole();
$invoice = Conversation::query()->where('title', 'Invoice copy')->sole();
$hours = Conversation::query()->where('title', 'Opening hours')->sole();
$limiter = RateLimiter::limiter('chat') ?? $fail('no rate limiter named chat');
$limitFor = static function (User $user, string $ip, string $message) use ($limiter, $fail): Limit {
    $request = Request::create('/conversations/2/messages', 'POST', ['message' => $message], [], [],
        ['REMOTE_ADDR' => $ip]);
    $request->setUserResolver(fn () => $user);
    $limits = Collection::wrap($limiter($request));
    if ($limits->count() !== 1 || ! $limits->first() instanceof Limit) {
        $fail('the chat limiter does not return one Limit');
    }

    return $limits->first();
};
$limit = $limitFor($marco, '10.0.0.1', 'a');
$max = $limit->maxAttempts;
if ($limit->decaySeconds !== 60 || $max < 1) {
    $fail('the chat limit is not a number of messages per minute');
}
if ((string) $limitFor($marco, '10.0.0.2', 'b')->key !== (string) $limit->key
    || (string) $limitFor($nora, '10.0.0.1', 'a')->key === (string) $limit->key) {
    $fail('the limit is not counted by the signed-in user (by() must use the user id only)');
}
// 4. The page: the 429 sentence in failure(), the handler as the lesson wrote it.
$jar = [];
$send = static function (string $method, string $uri, array $data = [], ?string $json = null, array $headers = []) use ($app, $kernel, &$jar): Response {
    $app['session.store']->flush();
    $app['auth']->forgetGuards();
    $server = ['HTTP_ACCEPT' => 'text/html'];
    if ($json !== null) {
        $server = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'];
    }
    foreach ($headers as $name => $value) {
        $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
    }
    $request = Request::create($uri, $method, $data, $jar, [], $server, $json);
    $response = $kernel->handle($request);
    foreach ($response->headers->getCookies() as $cookie) {
        $jar[$cookie->getName()] = $cookie->getValue();
    }
    $kernel->terminate($request, $response);

    return $response;
};
$expect = static function (Response $response, int $status, string $what) use ($fail): Response {
    if ($response->getStatusCode() !== $status) {
        $fail("{$what}: status {$response->getStatusCode()}, expected {$status}");
    }

    return $response;
};
$field = static function (Response $page) use ($fail): string {
    preg_match('/name="_token" value="([^"]+)"/', (string) $page->getContent(), $found)
        ?: $fail('the page has no CSRF field');

    return $found[1];
};
$signIn = static function (string $name) use ($send, $field, $expect): void {
    $form = $expect($send('GET', '/login'), 200, 'GET /login');
    $expect($send('POST', '/login', [
        '_token' => $field($form), 'name' => $name, 'password' => 'support-demo',
    ]), 302, "sign in as {$name}");
};
// What the page script sends: JSON, Accept JSON and the token of the meta tag.
$post = static function (Conversation $conversation, string $message, Response $page) use ($send): Response {
    preg_match('/<meta name="csrf-token" content="([^"]+)"/', (string) $page->getContent(), $meta);

    return $send('POST', "/conversations/{$conversation->id}/messages", [],
        (string) json_encode(['message' => $message]), ['X-CSRF-TOKEN' => $meta[1] ?? '']);
};
$json = static fn (Response $response): array => (array) json_decode((string) $response->getContent(), true);
$rows = static fn (Conversation $c): int => ChatMessage::query()->where('thread_id', $c->threadId())->count();
// A guest is answered 401 by the auth middleware before the limiter runs.
$expect($send('POST', "/conversations/{$invoice->id}/messages", [], '{"message":"hi"}',
    ['Sec-Fetch-Site' => 'same-origin']), 401, 'a guest posting a message');
$signIn('Marco');
$page = $expect($send('GET', "/conversations/{$invoice->id}"), 200, 'Marco\'s page');
$html = (string) $page->getContent();
$from = strpos($html, 'function failure(');
$to = strpos($html, "form.addEventListener('submit'");
if ($from === false || $to === false) {
    $fail('the page script has no failure() or no submit handler');
}
$failure = substr($html, $from, $to - $from);
if (preg_match('/429\)\s*return\s*\'Too many messages\. Wait a minute\.\'/', $failure) !== 1) {
    $fail('failure() does not return "Too many messages. Wait a minute." for 429');
}
$end = strpos($html, '</script>', $to);
if (sha1($flat(substr($html, $to, $end - $to))) !== '6f863e2b0951d03da3a14a43d95de8253d9d95cb') {
    $fail('the submit handler changed: a 429 is one of the "every other error" cases it already handles');
}
// 5. Marco: $max refusals by validation, then the limit, also for a real question.
for ($i = 1; $i <= $max; $i++) {
    $refused = $expect($post($invoice, '   ', $page), 422, "Marco's message {$i} of three spaces");
    if (($json($refused)['errors']['message'][0] ?? null) !== 'The message field is required.') {
        $fail('three spaces are not refused with "The message field is required."');
    }
}
$expect($post($invoice, '   ', $page), 429, 'Marco\'s message over the limit');
$expect($post($invoice, 'My invoice number is 4417', $page), 429, 'a real question over the limit');
if ($fake->getCallCount() !== 0 || $rows($invoice) !== 0) {
    $fail('a refused message reached the agent or was stored');
}
// 6. Nora, in the same minute, has her own count.
$expect($send('POST', '/logout', ['_token' => $field($page)]), 302, 'Marco signs out');
$signIn('Nora');
$page = $expect($send('GET', "/conversations/{$hours->id}"), 200, 'Nora\'s page');
$expect($post($hours, '   ', $page), 422, 'Nora\'s three spaces in the same minute');
// 7. A minute later Marco's messages go through again.
Carbon::setTestNow(Carbon::now()->addSeconds(61));
$expect($send('POST', '/logout', ['_token' => $field($page)]), 302, 'Nora signs out');
$signIn('Marco');
$page = $expect($send('GET', "/conversations/{$invoice->id}"), 200, 'Marco\'s page a minute later');
$answered = $expect($post($invoice, 'My invoice number is 4417', $page), 200, 'Marco a minute later');
if ($json($answered) !== ['answer' => 'Noted.'] || $fake->getCallCount() !== 1 || $rows($invoice) !== 2) {
    $fail('after the minute the message does not reach the agent');
}
echo "Limit: {$max} messages a minute for each user".PHP_EOL;
echo 'MESSAGE LIMIT EXERCISE OK'.PHP_EOL;
