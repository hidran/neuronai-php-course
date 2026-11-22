<?php

declare(strict_types=1);
// Check of the exercise "the last line of each conversation in the list".
// Usage: php exercise-last-line-check.php <your project>
// It runs the project's OWN seeders, classes, routes and views (nothing of
// the lesson is loaded over them) on an in-memory SQLite database, through
// the HTTP kernel with a cookie jar, as a browser would. Views compile into a
// temporary folder. A FakeAIProvider is bound and must stay unused: the list
// reads stored messages and never calls a model.
// It ends with "LAST LINE EXERCISE OK" when the acceptance list holds.

use App\Http\Controllers\ConversationController;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Support\Transcript;
use App\Support\TranscriptLine;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Chat\Messages\ToolCallMessage;
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
$compiled = sys_get_temp_dir().'/exercise-last-line-'.bin2hex(random_bytes(6));
mkdir($compiled, 0700, true);
config(['view.compiled' => $compiled]);
register_shutdown_function(static function () use ($compiled): void {
    array_map('unlink', glob("{$compiled}/*") ?: []);
    rmdir($compiled);
});
$fake = new FakeAIProvider;
$app->instance(AIProviderInterface::class, $fake);
// 1. What must stay as the lesson left it (compared without whitespace).
$flat = static fn (string $text): string => preg_replace('/\s+/', ' ', trim($text));
foreach ([
    'routes/web.php' => '70e589150570c3d5d6a2f55fa713451240f97751',
    'app/Support/TranscriptLine.php' => '001e3f59107ef75449cceb0ef0bf37fef3485786',
    'app/Policies/ConversationPolicy.php' => '639d0ca2062e5d9f4f78b18ad61134a920685874',
] as $file => $sha) {
    if (sha1($flat((string) @file_get_contents("{$lab}/{$file}"))) !== $sha) {
        $fail("{$file} changed: the exercise does not touch it");
    }
}
$show = new ReflectionMethod(ConversationController::class, 'show');
$source = file($show->getFileName());
$showText = implode('', array_slice($source, $show->getStartLine() - 1, $show->getEndLine() - $show->getStartLine() + 1));
if ($flat($showText) !== 'public function show( Conversation $conversation, Transcript $transcript, ): View {'
    .' Gate::authorize(\'view\', $conversation); return view(\'conversations.show\', ['
    .' \'conversation\' => $conversation, \'lines\' => $transcript->for($conversation), ]); }') {
    $fail('the show action changed: the exercise does not touch it');
}
foreach (['database/seeders/DemoSeeder.php', 'app/Support/Transcript.php',
    'app/Http/Controllers/ConversationController.php'] as $file) {
    if (str_contains((string) file_get_contents("{$lab}/{$file}"), 'Agent')) {
        $fail("{$file} names an agent: the list only reads stored messages");
    }
}
// 2. The seeder: three conversations, the new one third, its line written by the store.
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--force' => true]);
$question = 'Can I print the <b>return label</b> at home, or do you send it by mail?';
$expected = [1 => ['Nora', 'Opening hours'], 2 => ['Marco', 'Invoice copy'], 3 => ['Nora', 'Return label']];
$seeded = Conversation::query()->orderBy('id')->get()
    ->mapWithKeys(fn (Conversation $c) => [$c->id => [$c->user->name, $c->title]])->all();
if ($seeded !== $expected) {
    $fail('the seeded conversations are not Opening hours (Nora), Invoice copy (Marco), Return label (Nora), in this order');
}
[$hours, $invoice, $label] = [Conversation::find(1), Conversation::find(2), Conversation::find(3)];
$rows = ChatMessage::query()->where('thread_id', $label->threadId())->get();
if ($rows->count() !== 1 || $rows[0]->role !== 'user' || ($rows[0]->content[0]['content'] ?? null) !== $question
    || (string) $rows[0]->message_id === '') {
    $fail('Return label does not hold the one question, appended through the message store');
}
if (ChatMessage::query()->where('thread_id', $hours->threadId())->count() !== 4) {
    $fail('Opening hours no longer holds its four seeded messages');
}
// 3. The reader: last() returns the last line or null.
if (! method_exists(Transcript::class, 'last')) {
    $fail('Transcript has no last() method');
}
$type = new ReflectionMethod(Transcript::class, 'last')->getReturnType();
if (! $type instanceof ReflectionNamedType || $type->getName() !== TranscriptLine::class || ! $type->allowsNull()) {
    $fail('last() does not return ?TranscriptLine');
}
$reader = $app->make(Transcript::class);
$said = static fn (?TranscriptLine $line): ?array => $line === null ? null : [$line->role, $line->text];
if ($said($reader->last($hours)) !== ['assistant', 'On Saturday we are closed.']
    || $said($reader->last($label)) !== ['user', $question] || $reader->last($invoice) !== null) {
    $fail('last() does not return the last stored line, or null for an empty conversation');
}
// 4. A browser: sign in, read the list.
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
$expect = static function (Response $response, int $status, string $what) use ($fail): Response {
    if ($response->getStatusCode() !== $status) {
        $fail("{$what}: status {$response->getStatusCode()}, expected {$status}");
    }

    return $response;
};
$signIn = static function (string $name) use ($send, $token, $expect): void {
    $form = $expect($send('GET', '/login'), 200, 'GET /login');
    $expect($send('POST', '/login', [
        '_token' => $token($form), 'name' => $name, 'password' => 'support-demo',
    ]), 302, "sign in as {$name}");
};
// The text of every element with the class "preview", in page order, tags
// removed and spaces collapsed; escaped characters stay as entities.
$previews = static function (string $html) use ($flat): array {
    preg_match_all('/<p\b[^>]*\bclass="[^"]*\bpreview\b[^"]*"[^>]*>(.*?)<\/p>/s', $html, $found);

    return array_map(fn (string $inner) => $flat(strip_tags($inner)), $found[1]);
};
$titles = static function (string $html, array $all): array {
    $at = [];
    foreach ($all as $title) {
        if (($pos = strpos($html, ">{$title}<")) !== false) {
            $at[$pos] = $title;
        }
    }
    ksort($at);

    return array_values($at);
};
$all = ['Opening hours', 'Invoice copy', 'Return label', 'Refund question'];
$labelPreview = 'You: Can I print the &lt;b&gt;return label&lt;/b&gt; at home, or do you send...';
$signIn('Nora');
$page = $expect($send('GET', '/conversations'), 200, 'Nora\'s list');
$html = (string) $page->getContent();
if ($titles($html, $all) !== ['Return label', 'Opening hours']) {
    $fail('Nora\'s list does not show Return label, then Opening hours');
}
if ($previews($html) !== [$labelPreview, 'Support: On Saturday we are closed.']) {
    $fail('Nora\'s previews are not the expected two lines: '.json_encode($previews($html)));
}
if (str_contains($html, '<b>return label</b>')) {
    $fail('the stored text reaches the page as markup: print it with double curly braces');
}
// A conversation started from the form has no line yet.
$expect($send('POST', '/conversations', ['_token' => $token($page), 'title' => 'Refund question']),
    302, 'start a conversation');
$html = (string) $expect($send('GET', '/conversations'), 200, 'Nora\'s list again')->getContent();
if ($titles($html, $all) !== ['Refund question', 'Return label', 'Opening hours']
    || $previews($html) !== ['No messages yet.', $labelPreview, 'Support: On Saturday we are closed.']) {
    $fail('a new conversation does not show "No messages yet." under its title');
}
// 5. Marco: his own list only, and 403 on Nora's conversations.
$expect($send('POST', '/logout', ['_token' => $token($page)]), 302, 'sign out');
$signIn('Marco');
$html = (string) $expect($send('GET', '/conversations'), 200, 'Marco\'s list')->getContent();
if ($titles($html, $all) !== ['Invoice copy'] || $previews($html) !== ['No messages yet.']
    || str_contains($html, 'return label') || str_contains($html, 'Saturday')) {
    $fail('Marco\'s list is not his one conversation with "No messages yet.", or it shows a line of Nora\'s');
}
$expect($send('GET', '/conversations/3'), 403, 'Marco on /conversations/3');
// 6. last() keeps the reader's rules: rows without text are skipped, archived rows count.
$store = $app->make(MessageStoreInterface::class);
$store->append($label->threadId(), new ToolCallMessage(null, []));
if ($said($reader->last($label)) !== ['user', $question]) {
    $fail('a stored tool call became the last line: build last() on for()');
}
$store->archive($label->threadId(), 2);
if ($said($reader->last($label)) !== ['user', $question]) {
    $fail('an archived row no longer counts: build last() on for()');
}
if ($fake->getCallCount() !== 0) {
    $fail('a request reached the model provider');
}
echo 'LAST LINE EXERCISE OK'.PHP_EOL;
