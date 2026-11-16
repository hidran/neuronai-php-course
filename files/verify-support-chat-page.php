<?php
declare(strict_types=1);
// Offline check of "A support chat in the browser" (no model call: a
// FakeAIProvider is bound and must stay unused; an in-memory SQLite database:
// the lab's database file is never touched; views compile into a temporary
// folder, not into the lab's storage).
// Usage: php verify-support-chat-page.php <lab>
// <lab> is the Laravel lab built by this lesson's prepare.sh, at the start or at
// the end of the lesson: this folder's final classes, routes and views are
// loaded over the lab's own, so both states are checked against the same code.
// The requests go through the HTTP kernel with the cookies a browser would
// send back: the login form, the session and the CSRF token are the real ones.
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\LoginController;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Support\Transcript;
use App\Support\TranscriptLine;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\History\EloquentMessageStore;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
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
    'BCRYPT_ROUNDS' => '4',
] as $name => $value) {
    putenv("{$name}={$value}");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}
require $lab . '/vendor/autoload.php';
// This folder's final classes, before the autoloader finds the lab's own.
foreach (['TranscriptLine', 'Transcript', 'LoginController',
    'ConversationController', 'DemoSeeder', 'DatabaseSeeder'] as $class) {
    require __DIR__ . "/{$class}.php";
}
$app = require $lab . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
// Laravel's handler would render a failure and exit 0: report it and exit 1.
set_exception_handler(static function (Throwable $e): never {
    fwrite(STDERR, 'VERIFY FAILED: ' . $e->getMessage() . PHP_EOL);
    exit(1);
});
$fail = static fn (string $why) => throw new RuntimeException($why);
// This folder's views and routes over the lab's.
$views = sys_get_temp_dir() . '/verify-support-chat-' . bin2hex(random_bytes(6));
foreach ([
    'app.blade.php' => 'layouts/app.blade.php',
    'login.blade.php' => 'login.blade.php',
    'index.blade.php' => 'conversations/index.blade.php',
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
Route::middleware('web')->group(__DIR__ . '/web.php');
$routes = Route::getRoutes();
$routes->refreshNameLookups();
$routes->refreshActionLookups();
// No request of this lesson may reach a model: the fake must stay unused.
$fake = new FakeAIProvider();
$app->instance(AIProviderInterface::class, $fake);
// A browser in a few lines: one cookie jar; before every request the session
// store is emptied (it then loads what the cookie names) and the guards are
// forgotten, so nothing survives between requests except the cookies, as in
// separate PHP processes.
$jar = [];
$send = static function (string $method, string $uri, array $data = []) use ($app, $kernel, &$jar): Response {
    $app['session.store']->flush();
    $app['auth']->forgetGuards();
    $request = Request::create($uri, $method, $data, $jar);
    $response = $kernel->handle($request);
    foreach ($response->headers->getCookies() as $cookie) {
        $jar[$cookie->getName()] = $cookie->getValue();
    }
    $kernel->terminate($request, $response);
    return $response;
};
$token = static function (Response $page) use ($fail): string {
    preg_match('/name="_token" value="([^"]+)"/', (string) $page->getContent(), $found)
        ?: $fail('the page has no CSRF field');
    return $found[1];
};
// The path a redirect points to ("/" for the bare host).
$target = static fn (Response $response): string =>
    parse_url((string) $response->headers->get('Location'), PHP_URL_PATH) ?: '/';
$expect = static function (Response $response, int $status, string $what) use ($fail): Response {
    if ($response->getStatusCode() !== $status) {
        $fail("{$what}: status {$response->getStatusCode()}, expected {$status}");
    }
    return $response;
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
// 1. What the narration says about the installed packages and our classes.
$block = new UserMessage('probe')->getContentBlocks()[0]->toArray();
if (($block['type'] ?? null) !== NeuronAI\Chat\Enums\ContentBlockType::TEXT
    || ($block['content'] ?? null) !== 'probe'
    || json_decode((string) json_encode($block), true)['type'] !== 'text') {
    $fail('a text block is no longer {type: text, content}: re-check segment 4c');
}
if (MessageRole::USER->value !== 'user' || MessageRole::ASSISTANT->value !== 'assistant') {
    $fail('the stored role values changed: re-check segment 4');
}
if (!(new ReflectionClass(Transcript::class))->isReadOnly()
    || !(new ReflectionClass(TranscriptLine::class))->isReadOnly()
    || !(new ReflectionClass(LoginController::class))->isFinal()
    || !(new ReflectionClass(ConversationController::class))->isFinal()) {
    $fail('Transcript and TranscriptLine are readonly, the controllers final');
}
// 2. The lab's migrations and the seeders: two demo users, a conversation
//    each, a stored transcript on the first one, no stock test user.
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--force' => true]);
$names = User::query()->orderBy('id')->pluck('name')->all();
if ($names !== ['Nora', 'Marco']) {
    $fail('the seeded users are not the two demo users');
}
foreach (User::all() as $user) {
    if (!str_ends_with($user->email, '@example.test') || !Hash::check('support-demo', $user->password)) {
        $fail('a demo user has a real looking address or an unexpected password');
    }
}
$hours = Conversation::query()->where('title', 'Opening hours')->sole();
$invoice = Conversation::query()->where('title', 'Invoice copy')->sole();
if (Conversation::query()->count() !== 2 || $hours->user->name !== 'Nora' || $invoice->user->name !== 'Marco') {
    $fail('the seeded conversations are not one per demo user');
}
$stored = ChatMessage::query()->where('thread_id', $hours->threadId())->orderBy('id')->get();
if ($stored->pluck('role')->all() !== ['user', 'assistant', 'user', 'assistant']
    || ($stored[0]->content[0]['type'] ?? null) !== 'text'
    || ($stored[0]->content[0]['content'] ?? null) !== 'When does support open?'
    || $stored->contains(fn (ChatMessage $row) => $row->message_id === null || $row->message_id === '')
    || ChatMessage::query()->where('thread_id', $invoice->threadId())->exists()) {
    $fail('the seeded transcript is not four store-written rows on the first thread');
}
$said = ['When does support open?', 'We open at nine, Monday to Friday.',
    'And on Saturday?', 'On Saturday we are closed.'];
// 3. Transcript: role and text, in key order.
$lines = new Transcript()->for($hours);
if (array_map(fn (TranscriptLine $line) => [$line->role, $line->text], $lines) !== [
    ['user', $said[0]], ['assistant', $said[1]], ['user', $said[2]], ['assistant', $said[3]],
] || new Transcript()->for($invoice) !== []) {
    $fail('Transcript does not return the stored lines in order');
}
// 4. A guest is sent to the login, from the list, a page and the home page.
$expect($send('GET', '/'), 302, 'GET /');
foreach (['/conversations', "/conversations/{$hours->id}"] as $uri) {
    $response = $expect($send('GET', $uri), 302, "guest GET {$uri}");
    if ($target($response) !== '/login') {
        $fail("a guest on {$uri} is not sent to /login");
    }
}
// 5. The login form asks for a name and a password, never an address.
$form = $expect($send('GET', '/login'), 200, 'GET /login');
$html = (string) $form->getContent();
if (!str_contains($html, 'name="name"') || !str_contains($html, 'type="password"')
    || stripos($html, 'email') !== false || str_contains($html, '@example.test')) {
    $fail('the login form is not name and password only');
}
$wrong = $expect($send('POST', '/login', [
    '_token' => $token($form), 'name' => 'Nora', 'password' => 'not-the-password',
]), 302, 'POST /login with a wrong password');
$again = $expect($send('GET', '/login'), 200, 'GET /login after a wrong password');
if ($target($wrong) !== '/login' || !str_contains((string) $again->getContent(), 'Wrong name or password.')
    || !str_contains((string) $again->getContent(), 'value="Nora"')) {
    $fail('a wrong password does not come back to the form with the message and the name');
}
$before = DB::table('sessions')->pluck('id')->all();
$signedIn = $expect($send('POST', '/login', [
    '_token' => $token($again), 'name' => 'Nora', 'password' => 'support-demo',
]), 302, 'POST /login');
// intended(): the page the guest asked for last, before the login.
if ($target($signedIn) !== "/conversations/{$hours->id}") {
    $fail('after the login the visitor is not sent to the page asked for: ' . $target($signedIn));
}
// The guest's session identifier does not survive the login (the guard
// migrates the session; store() also regenerates it, as Laravel documents).
if (count($before) !== 1 || DB::table('sessions')->whereIn('id', $before)->exists()
    || !str_contains((string) file_get_contents(__DIR__ . '/LoginController.php'), '$request->session()->regenerate();')) {
    $fail('the session identifier was not regenerated at login');
}
// 6. The owner: her list, her transcript in order, no address on any page.
$list = (string) $expect($send('GET', '/conversations'), 200, 'GET /conversations')->getContent();
if (!str_contains($list, 'Opening hours') || str_contains($list, 'Invoice copy')
    || !str_contains($list, 'Signed in as Nora') || str_contains($list, '@example.test')) {
    $fail('the list is not the signed-in user\'s own conversations');
}
$page = (string) $expect($send('GET', "/conversations/{$hours->id}"), 200, 'the owner\'s page')->getContent();
if (!$inOrder($page, $said) || str_contains($page, '@example.test')) {
    $fail('the page does not show the stored lines in order');
}
if (!$inOrder($page, ['<li class="user">', '<li class="assistant">', '<li class="user">', '<li class="assistant">'])) {
    $fail('the lines do not carry their roles');
}
// The message form is on the page and disabled; nothing on it can send.
if (!str_contains($page, '<fieldset disabled>') || !str_contains($page, 'id="message-form"')
    || stripos($page, '<script') !== false) {
    $fail('the message form is not the disabled one, or the page has a script');
}
if (preg_match('/<form id="message-form"[^>]*action=/', $page) === 1) {
    $fail('the message form has an action already');
}
// 7. Archived rows stay in the transcript; other roles and rows without text never show.
$store = $app->make(MessageStoreInterface::class);
if (!$store instanceof EloquentMessageStore) {
    $fail('the container does not bind EloquentMessageStore');
}
$store->archive($hours->threadId(), 2);
$store->append($hours->threadId(), new Message(MessageRole::DEVELOPER, 'internal note for the model'));
if (count($store->loadActive($hours->threadId())) !== 3
    || ChatMessage::query()->where('thread_id', $hours->threadId())->whereNotNull('archived_at')->count() !== 2) {
    $fail('archive() did not archive the first two rows');
}
$store->append($hours->threadId(), new AssistantMessage('Use <b>bold</b> & co.'));
$page = (string) $expect($send('GET', "/conversations/{$hours->id}"), 200, 'the page after archiving')->getContent();
if (!$inOrder($page, $said) || str_contains($page, 'internal note for the model')) {
    $fail('archived rows left the transcript, or a row of another role shows');
}
// A stored text is data, never markup.
if (!str_contains($page, 'Use &lt;b&gt;bold&lt;/b&gt; &amp; co.') || str_contains($page, '<b>bold</b>')) {
    $fail('the page does not escape the stored text');
}
// Tool traffic and a message without a text block are stored with the user or
// the assistant role and have no words for a person: no line, no empty bubble.
$store->append($hours->threadId(), new ToolCallMessage(null, []));
$store->append($hours->threadId(), new ToolResultMessage([]));
$store->append($hours->threadId(), new UserMessage(new ImageContent('picture.png', SourceType::URL)));
$silent = ChatMessage::query()->where('thread_id', $hours->threadId())->orderByDesc('id')->limit(3)->get();
if ($silent->pluck('role')->all() !== ['user', 'user', 'assistant']
    || ($silent[0]->content[0]['type'] ?? null) !== 'image' || $silent[1]->content !== [] || $silent[2]->content !== []) {
    $fail('tool messages are no longer stored as assistant and user rows without text: re-check segment 4b');
}
// A spoken line after the silent rows: the result must still be a list.
$store->append($hours->threadId(), new AssistantMessage('Anything else?'));
$lines = new Transcript()->for($hours);
$page = (string) $expect($send('GET', "/conversations/{$hours->id}"), 200, 'the page with tool rows')->getContent();
if (count($lines) !== 6 || !array_is_list($lines) || substr_count($page, '<li class="') !== 6
    || preg_match('/<li class="[a-z]+">\s*<\/li>/', $page) === 1 || !$inOrder($page, [...$said, 'Anything else?'])) {
    $fail('a row without text is rendered as a line');
}
// 8. A new conversation belongs to the signed-in user, whatever the form says.
$list = $expect($send('GET', '/conversations'), 200, 'GET /conversations');
$created = $expect($send('POST', '/conversations', [
    '_token' => $token($list), 'title' => 'Refund question', 'user_id' => $invoice->user_id,
]), 302, 'POST /conversations');
$refund = Conversation::query()->where('title', 'Refund question')->sole();
if ($refund->user_id !== $hours->user_id || $target($created) !== "/conversations/{$refund->id}") {
    $fail('a new conversation is not the signed-in user\'s, or the redirect is wrong');
}
$empty = (string) $expect($send('GET', "/conversations/{$refund->id}"), 200, 'the new page')->getContent();
if (!str_contains($empty, 'No messages yet.')) {
    $fail('an empty conversation does not say so');
}
foreach (['', str_repeat('x', 81)] as $title) {
    $expect($send('POST', '/conversations', ['_token' => $token($list), 'title' => $title]), 302, 'an invalid title');
}
if (Conversation::query()->count() !== 3) {
    $fail('an invalid title created a conversation');
}
// A post without the CSRF token is refused.
$expect($send('POST', '/conversations', ['title' => 'No token']), 419, 'POST without a token');
// 9. Sign out, then the other user: his own list, 403 on her conversation.
$out = $expect($send('POST', '/logout', ['_token' => $token($list)]), 302, 'POST /logout');
if ($target($out) !== '/login' || $target($expect($send('GET', '/conversations'), 302, 'after logout')) !== '/login') {
    $fail('signing out does not end the session');
}
$form = $expect($send('GET', '/login'), 200, 'GET /login');
$expect($send('POST', '/login', [
    '_token' => $token($form), 'name' => 'Marco', 'password' => 'support-demo',
]), 302, 'POST /login as the other user');
$list = (string) $expect($send('GET', '/conversations'), 200, 'the other user\'s list')->getContent();
if (!str_contains($list, 'Invoice copy') || str_contains($list, 'Opening hours')
    || str_contains($list, 'Refund question') || !str_contains($list, 'Signed in as Marco')) {
    $fail('the other user does not see only his own conversations');
}
$forbidden = $expect($send('GET', "/conversations/{$hours->id}"), 403, 'the other user on her page');
if (str_contains((string) $forbidden->getContent(), $said[0])) {
    $fail('the 403 page leaks the transcript');
}
// A signed-in visitor of the login page goes home, which is the list.
$home = $expect($send('GET', '/login'), 302, 'GET /login when signed in');
if ($target($home) !== '/' || $target($expect($send('GET', '/'), 302, 'GET /')) !== '/conversations') {
    $fail('a signed-in visitor of /login does not end on the list');
}
// 10. Nothing called a model, and no web message route exists in this lesson's routes.
if ($fake->getCallCount() !== 0) {
    $fail('a request of this lesson reached the provider');
}
if (str_contains((string) file_get_contents(__DIR__ . '/web.php'), 'messages')) {
    $fail('this lesson\'s routes already have a message route');
}
echo 'SUPPORT CHAT PAGE OK' . PHP_EOL;
