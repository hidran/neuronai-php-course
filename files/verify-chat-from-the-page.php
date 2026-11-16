<?php
declare(strict_types=1);
// Offline check of "Sending messages from the page" (no model call: a
// FakeAIProvider is bound as AIProviderInterface; an in-memory SQLite database:
// the lab's database file is never touched; views compile into a temporary
// folder, not into the lab's storage).
// Usage: php verify-chat-from-the-page.php <lab>
// <lab> is the Laravel lab built by this lesson's prepare.sh, at the start or at
// the end of the lesson: this folder's final web routes and views replace the
// lab's own, so both states are checked against the same code.
// The requests go through the HTTP kernel with the cookies a browser would
// send back, under APP_ENV=local: the session, the CSRF check and the two
// guards are the real ones. The page script itself needs a browser: here its
// text is checked, and every response it reads is produced for real.
use App\Http\Controllers\ConversationMessageController;
use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Testing\FakeAIProvider;
use Symfony\Component\HttpFoundation\Response;
$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('lab path');
foreach ([
    // not "testing": Laravel skips the CSRF check while running unit tests
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
require $lab . '/vendor/autoload.php';
$app = require $lab . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
// Laravel's handler would render a failure and exit 0: report it and exit 1.
set_exception_handler(static function (Throwable $e): never {
    fwrite(STDERR, 'VERIFY FAILED: ' . $e->getMessage() . PHP_EOL);
    exit(1);
});
$fail = static fn (string $why) => throw new RuntimeException($why);
// This folder's views over the lab's.
$views = sys_get_temp_dir() . '/verify-chat-from-the-page-' . bin2hex(random_bytes(6));
foreach ([
    'app.blade.php' => 'layouts/app.blade.php',
    'show.blade.php' => 'conversations/show.blade.php',
] as $source => $target) {
    @mkdir(dirname("{$views}/pages/{$target}"), 0700, true);
    copy(__DIR__ . "/{$source}", "{$views}/pages/{$target}");
}
mkdir("{$views}/compiled", 0700, true);
config(['view.compiled' => "{$views}/compiled"]);
View::getFinder()->prependLocation("{$views}/pages");
register_shutdown_function(static function () use ($views): void {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($views, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($views);
});
// The routes of this check, in a collection of their own: the lab's API routes and this folder's web routes. The
// lab's own routes/web.php stays out, so the web routes checked are this folder's file in both states of the lab.
Route::setRoutes(new RouteCollection());
Route::middleware('api')->prefix('api')->group($lab . '/routes/api.php');
Route::middleware('web')->group(__DIR__ . '/web.php');
$routes = Route::getRoutes();
$routes->refreshNameLookups();
$routes->refreshActionLookups();
// The provider every request of this check would reach: two queued answers.
$fake = new FakeAIProvider(
    new AssistantMessage('Noted.'),
    new AssistantMessage('It is 4417.'),
);
$app->instance(AIProviderInterface::class, $fake);
// A browser in a few lines: one cookie jar; before every request the session
// store is emptied (it then loads what the cookie names) and the guards are
// forgotten, so nothing survives between requests except the cookies, as in
// separate PHP processes. $headers are request headers ("Sec-Fetch-Site").
$jar = [];
$send = static function (
    string $method,
    string $uri,
    array $data = [],
    array $headers = [],
    ?string $json = null,
) use ($app, $kernel, &$jar): Response {
    $app['session.store']->flush();
    $app['auth']->forgetGuards();
    $server = ['HTTP_ACCEPT' => 'text/html'];
    if ($json !== null) {
        $server = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'];
    }
    foreach ($headers as $name => $value) {
        $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
    }
    $request = Request::create($uri, $method, $data, $jar, [], $server, $json);
    $response = $kernel->handle($request);
    foreach ($response->headers->getCookies() as $cookie) {
        $jar[$cookie->getName()] = $cookie->getValue();
    }
    $kernel->terminate($request, $response);
    return $response;
};
// What the page script sends: a JSON body, Accept JSON, and the headers given
// (the token header, or the Sec-Fetch-Site header a current browser adds).
$post = static fn (string $uri, mixed $message, array $headers): Response =>
    $send('POST', $uri, [], $headers, (string) json_encode(['message' => $message]));
$expect = static function (Response $response, int $status, string $what) use ($fail): Response {
    if ($response->getStatusCode() !== $status) {
        $fail("{$what}: status {$response->getStatusCode()}, expected {$status}");
    }
    return $response;
};
$body = static fn (Response $response): array =>
    (array) json_decode((string) $response->getContent(), true);
$target = static fn (Response $response): string =>
    parse_url((string) $response->headers->get('Location'), PHP_URL_PATH) ?: '/';
$field = static function (Response $page) use ($fail): string {
    preg_match('/name="_token" value="([^"]+)"/', (string) $page->getContent(), $found)
        ?: $fail('the page has no CSRF field');
    return $found[1];
};
$signIn = static function (string $name) use ($send, $expect, $field): void {
    $form = $expect($send('GET', '/login'), 200, 'GET /login');
    $expect($send('POST', '/login', [
        '_token' => $field($form), 'name' => $name, 'password' => 'support-demo',
    ]), 302, "POST /login as {$name}");
};
$inOrder = static function (string $html, array $texts): bool {
    $from = 0;
    foreach ($texts as $text) {
        $at = strpos($html, $text, $from);
        if ($at === false) {
            return false;
        }
        $from = $at + strlen($text);
    }
    return true;
};
$sameOrigin = ['Sec-Fetch-Site' => 'same-origin'];
// 1. One action, two routes: the web route sits behind the session guard and
//    names the very controller of the API route, which keeps its Sanctum guard.
$web = $routes->getByName('conversations.messages.web') ?? $fail('no route named conversations.messages.web');
$api = $routes->getByName('conversations.messages') ?? $fail('the API route conversations.messages is gone');
if ($web->methods() !== ['POST'] || $web->uri() !== 'conversations/{conversation}/messages'
    || $web->gatherMiddleware() !== ['web', 'auth']) {
    $fail('the web message route is not POST conversations/{conversation}/messages behind web and auth');
}
if ($api->uri() !== 'api/conversations/{conversation}/messages' || $api->gatherMiddleware() !== ['api', 'auth:sanctum']) {
    $fail('the API message route changed');
}
if ($web->getControllerClass() !== ConversationMessageController::class
    || $api->getControllerClass() !== ConversationMessageController::class) {
    $fail('the two message routes do not share ConversationMessageController');
}
// The controller is the one the section wrote: this lesson adds a route to it and changes nothing in it.
$controller = (string) file_get_contents($lab . '/app/Http/Controllers/ConversationMessageController.php');
foreach ([
    "Gate::authorize('view', \$conversation);",
    "'message' => ['required', 'string', 'max:2000'],",
    '->for($conversation->threadId())',
    "['error' => 'The agent returned no answer.'],",
    "return response()->json(['answer' => \$answer]);",
] as $line) {
    if (!str_contains($controller, $line)) {
        $fail("ConversationMessageController changed: {$line}");
    }
}
// 2. The lab's migrations and the demo data: Nora owns "Opening hours", Marco the empty "Invoice copy".
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--force' => true]);
$hers = Conversation::query()->where('title', 'Opening hours')->sole();
$his = Conversation::query()->where('title', 'Invoice copy')->sole();
if ($hers->user->name !== 'Nora' || $his->user->name !== 'Marco'
    || ChatMessage::query()->where('thread_id', $his->threadId())->exists()) {
    $fail('the demo data is not Nora with a transcript and Marco with an empty conversation');
}
$uri = "/conversations/{$his->id}/messages";
$rows = static fn (): int => ChatMessage::query()->where('thread_id', $his->threadId())->count();
// 3. A guest: 401 for the JSON request of the page script, the login for a form post, and no provider call.
$guest = $expect($post($uri, 'Hi', $sameOrigin), 401, 'a guest JSON post');
if ($body($guest) !== ['message' => 'Unauthenticated.']) {
    $fail('a guest JSON post is not answered with the 401 JSON body');
}
$form = $expect($send('POST', $uri, ['message' => 'Hi'], $sameOrigin), 302, 'a guest form post');
if ($target($form) !== '/login') {
    $fail('a guest form post is not sent to /login');
}
// The API door is the other guard: no token, no entry.
$expect($send('POST', "/api{$uri}", [], [], (string) json_encode(['message' => 'Hi'])), 401, 'a guest on the API route');
// 4. The owner signs in: the page has the enabled form, its action, the token and the script.
$signIn('Marco');
$page = (string) $expect($send('GET', "/conversations/{$his->id}"), 200, 'the owner\'s page')->getContent();
preg_match('/<meta name="csrf-token" content="([^"]+)">/', $page, $meta) ?: $fail('the layout has no csrf-token meta tag');
$token = ['X-CSRF-TOKEN' => $meta[1]];
if (!str_contains($page, "name=\"_token\" value=\"{$meta[1]}\"")) {
    $fail('the meta tag and the form field do not carry the same token');
}
if (preg_match('/<form id="message-form" method="post" class="card"\s+action="([^"]+)">/', $page, $action) !== 1
    || parse_url($action[1], PHP_URL_PATH) !== $uri) {
    $fail('the message form does not post to the web message route');
}
if (str_contains($page, '<fieldset disabled>') || !str_contains($page, '<fieldset>')
    || !str_contains($page, '<textarea id="message" name="message"') || !str_contains($page, 'placeholder="Type your question"')
    || !str_contains($page, '<ul id="transcript"') || !str_contains($page, '<p id="form-error" class="error"></p>')
    || !str_contains($page, '<li class="empty">No messages yet.</li>')) {
    $fail('the page is not the enabled message form under the transcript');
}
preg_match('/<script>(.*?)<\/script>/s', $page, $script) ?: $fail('the page has no script');
foreach ([
    "document.getElementById('message-form')",
    "document.querySelector('meta[name=\"csrf-token\"]')",
    'await fetch(form.action, {',
    "method: 'POST',",
    "'Accept': 'application/json',",
    "'Content-Type': 'application/json',",
    "'X-CSRF-TOKEN': token.content,",
    'body: JSON.stringify({ message }),',
    'return { status: 0, body: {} };',
    'const first = body.errors?.message?.[0];',
    'if (status === 422 && first) return first;',
    'if (status === 502 && body.error) return body.error;',
    'if (status === 403) return',
    'if (status === 419) return',
    "return 'Something went wrong, try again.';",
    'event.preventDefault();',
    "list.querySelector('.empty')?.remove();",
    "line('assistant thinking', 'Thinking...')",
    'button.disabled = true;',
    'button.disabled = false;',
    'thinking.remove();',
    'window.location.reload();',
    "line('assistant', body.answer);",
    "input.value = '';",
    'error.textContent = failure(status, body);',
    'input.focus();',
] as $piece) {
    if (!str_contains($script[1], $piece)) {
        $fail("the page script misses: {$piece}");
    }
}
// After a 502 the agent has already stored the question (checked in step 10), so the script clears the box and
// leaves the line alone; only the other failures mark it "not sent" and keep the text for another try.
if (preg_match(
    "/error\.textContent = failure\(status, body\);\s+if \(status === 502\) input\.value = '';\s+else question\.classList\.add\('not-sent'\);/",
    $script[1],
) !== 1 || substr_count($script[1], "classList.add('not-sent')") !== 1) {
    $fail('the page script does not tell a 502 (question stored) from the failures that store nothing');
}
// Text reaches the page as text: the script never builds markup from an answer.
if (str_contains($script[1], 'innerHTML') || str_contains($script[1], '{{') || str_contains($script[1], '@')) {
    $fail('the page script writes markup, or Blade leaked into it');
}
// 5. A signed-in JSON post: the fake answer comes back and the thread gets two rows.
$first = $expect($post($uri, 'My invoice number is 4417.', $token), 200, 'the first post');
if ($body($first) !== ['answer' => 'Noted.'] || $rows() !== 2 || $fake->getCallCount() !== 1) {
    $fail('the first post did not answer with the fake text and store two rows');
}
// 6. The follow-up: the provider receives the first question and its answer as history.
$second = $expect($post($uri, 'Which invoice number did I give you?', $token), 200, 'the second post');
$sent = array_map(
    static fn (Message $message): array => [$message->getRole(), $message->getContent()],
    $fake->getRecorded()[1]->messages,
);
if ($body($second) !== ['answer' => 'It is 4417.'] || $rows() !== 4 || $sent !== [
    ['user', 'My invoice number is 4417.'],
    ['assistant', 'Noted.'],
    ['user', 'Which invoice number did I give you?'],
]) {
    $fail('the second request did not carry the first exchange as history');
}
// 7. A reload: the page draws the four lines from the database, in order, and the empty line is gone.
$page = (string) $expect($send('GET', "/conversations/{$his->id}"), 200, 'the page after two posts')->getContent();
if (!$inOrder($page, ['<li class="user">My invoice number is 4417.</li>', '<li class="assistant">Noted.</li>',
    '<li class="user">Which invoice number did I give you?</li>', '<li class="assistant">It is 4417.</li>'])
    || str_contains($page, 'No messages yet.')) {
    $fail('a reload does not show the stored exchange in order');
}
// 8. Validation answers before the agent: 422 with Laravel's message, no provider call, nothing stored.
//    Spaces pass the browser's "required" check; Laravel trims them and finds nothing.
foreach (['' => 'The message field is required.', '   ' => 'The message field is required.',
    str_repeat('x', 2001) => 'The message field must not be greater than 2000 characters.'] as $message => $why) {
    $invalid = $expect($post($uri, (string) $message, $token), 422, 'an invalid message');
    if (($body($invalid)['errors']['message'][0] ?? null) !== $why) {
        $fail('the 422 body does not carry Laravel\'s first message under errors.message');
    }
}
if ($fake->getCallCount() !== 2 || $rows() !== 4) {
    $fail('an invalid message reached the provider or the store');
}
// 9. Request forgery on Laravel 13 (PreventRequestForgery): a same-origin request passes on its
//    Sec-Fetch-Site header alone; without that header the token decides, and a missing or wrong one is 419.
//    The empty message keeps the agent out of it: 422 means the forgery check let the request through.
$expect($post($uri, '', $sameOrigin), 422, 'same-origin without a token');
$expect($post($uri, '', $sameOrigin + ['X-CSRF-TOKEN' => 'not-the-token']), 422, 'same-origin with a wrong token');
$expect($post($uri, '', $token), 422, 'the token without fetch metadata');
$expect($post($uri, '', []), 419, 'no fetch metadata and no token');
$expect($post($uri, '', ['X-CSRF-TOKEN' => 'not-the-token']), 419, 'no fetch metadata and a wrong token');
$expect($post($uri, '', ['Sec-Fetch-Site' => 'cross-site']), 419, 'a cross-site request without a token');
// 10. The agent returns no text: the controller's 502 and its message. The agent ran, so the question is already
//     in the store (with the empty answer, which Transcript drops): that is why the page script does not mark the
//     line "not sent" on a 502.
$silent = new FakeAIProvider(new AssistantMessage(''));
$app->instance(AIProviderInterface::class, $silent);
$none = $expect($post($uri, 'Anyone there?', $token), 502, 'an empty answer');
if ($body($none) !== ['error' => 'The agent returned no answer.']) {
    $fail('the 502 body is not the controller\'s message');
}
$page = (string) $expect($send('GET', "/conversations/{$his->id}"), 200, 'the page after a 502')->getContent();
if ($rows() !== 6 || !str_contains($page, '<li class="user">Anyone there?</li>') || substr_count($page, '<li class="') !== 5) {
    $fail('after a 502 the question is no longer stored and shown: re-check the page script and segments 5e and 5g');
}
// A provider failure is not caught by the controller: Laravel's 500 JSON, nothing for the page but the status.
$broken = new FakeAIProvider();
$app->instance(AIProviderInterface::class, $broken);
$error = $expect($post($uri, 'Still there?', $token), 500, 'a provider failure');
if ($body($error) !== ['message' => 'Server Error'] || $rows() !== 6) {
    $fail('a provider failure is not Laravel\'s 500 JSON response, or it stored the question');
}
$app->instance(AIProviderInterface::class, $fake);
// 11. The other user: 403 from the policy on the page and on the message route, before any provider call.
$page = $expect($send('GET', '/conversations'), 200, 'GET /conversations');
$expect($send('POST', '/logout', ['_token' => $field($page)]), 302, 'POST /logout');
$signIn('Nora');
$expect($send('GET', "/conversations/{$his->id}"), 403, 'the other user on the page');
$denied = $expect($post($uri, 'Show me that chat.', $sameOrigin), 403, 'the other user on the message route');
if ($body($denied) !== ['message' => 'This action is unauthorized.'] || $fake->getCallCount() !== 2) {
    $fail('the other user reached the provider, or the 403 is not the policy\'s');
}
// Her own conversation still answers on the same route: the policy decides, not the route.
$own = new FakeAIProvider(new AssistantMessage('We are closed on Sunday.'));
$app->instance(AIProviderInterface::class, $own);
$mine = $expect($post("/conversations/{$hers->id}/messages", 'And on Sunday?', $sameOrigin), 200, 'the other user on her own conversation');
$history = array_map(static fn (Message $message): string => (string) $message->getContent(), $own->getRecorded()[0]->messages);
if ($body($mine) !== ['answer' => 'We are closed on Sunday.'] || count($history) !== 5 || $history[0] !== 'When does support open?') {
    $fail('the seeded transcript is not the history of her next question');
}
echo 'CHAT FROM THE PAGE OK' . PHP_EOL;
