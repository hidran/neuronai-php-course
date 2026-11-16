<?php
declare(strict_types=1);
// Offline check of "Testing the chat app without calling a model" (no model
// call: the tests bind a FakeAIProvider as AIProviderInterface; an in-memory
// SQLite database, as the lab's phpunit.xml sets it).
// Usage: php verify-testing-the-chat-app.php <lab>
// <lab> is the Laravel lab built by this lesson's prepare.sh, at the start or at
// the end of the lesson. The lab itself is never written: its application code
// (app, bootstrap, config, database, resources, routes, tests, phpunit.xml) is
// copied to a temporary folder, the lab's vendor folder is linked there, and
// PHPUnit runs on the copy with this folder's test files. No .env is read or
// copied: the copy has none, and a random APP_KEY is passed in the environment.
// What is checked:
//   1. the conventions of the final test file (plain assertions on the fake,
//      its own data, no seeder);
//   2. every typed stage of the file: the whole suite is green and holds the
//      number of tests the narration reads from PhpStorm's test runner;
//   3. the red run of the lesson: at the nine-test stage, without the policy
//      line of ConversationController::show, exactly one test fails, with the
//      message the narration reads;
//   4. every test can fail: each mutation of the application code below turns
//      exactly the expected tests red, and nothing else;
//   5. the exercise solution, and that both of its tests can fail too.
$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('lab path');
$fail = static function (string $why): never {
    fwrite(STDERR, 'VERIFY FAILED: ' . $why . PHP_EOL);
    exit(1);
};
set_exception_handler(static fn (Throwable $e) => $fail($e->getMessage()));
is_file("{$lab}/vendor/bin/phpunit") || $fail('the lab has no vendor/bin/phpunit');
const TEST = 'SupportChatPageTest.php';
const NAMES = [
    'test_guests_are_sent_to_the_login',
    'test_the_page_shows_stored_lines_in_order',
    'test_another_user_never_reaches_the_agent',
    'test_a_second_post_carries_the_history',
    'test_an_empty_message_never_calls_the_model',
];
// ---- 1. The final test file.
$final = (string) file_get_contents(__DIR__ . '/' . TEST);
preg_match_all('/^    public function (test_\w+)\(\): void$/m', $final, $found);
$found[1] === NAMES || $fail('the final file does not hold the five tests, in order');
foreach ([
    'declare(strict_types=1);' => 'strict types',
    'final class SupportChatPageTest extends TestCase' => 'a final test class',
    '    use RefreshDatabase;' => 'RefreshDatabase',
    '$this->app->instance(AIProviderInterface::class, $fake);' => 'the fake bound as AIProviderInterface',
    '$fake->getCallCount()' => 'a plain check of the call count',
    '$fake->getRecorded()[1]->messages' => 'the messages of the second recorded request',
    "\$store->append(\$thread, new UserMessage('When do you open?'));" => 'transcript rows appended through the store',
    'Conversation::factory()->create()' => 'its own conversations',
] as $needle => $what) {
    str_contains($final, $needle) || $fail("the final test file misses {$what}");
}
// The course convention: plain assertions, never the fake's own assert* methods.
preg_match('/\$fake->assert|->assertCallCount|->assertSent|->assertNothingSent/', $final) !== 1
    || $fail('the tests use the assert* methods of FakeAIProvider');
preg_match('/Seeder|->seed\(|Http::fake|Nora|Marco/', $final) !== 1
    || $fail('the tests depend on the demo seeder or on demo names');
foreach (explode("\n", rtrim($final, "\n")) as $n => $line) {
    ($line !== '' && rtrim($line) === $line && ! str_contains($line, "\t") && strlen($line) <= 72)
        || $fail('line ' . ($n + 1) . ' of the final file is blank, too long or has stray whitespace');
}
// ---- The private copy of the lab's code.
$work = sys_get_temp_dir() . '/verify-testing-the-chat-app-' . bin2hex(random_bytes(6));
$remove = static function (string $dir) use (&$remove): void {
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = "{$dir}/{$entry}";
        is_dir($path) && ! is_link($path) ? $remove($path) : unlink($path);
    }
    rmdir($dir);
};
register_shutdown_function(static function () use ($work, $remove): void {
    is_dir($work) && $remove($work);
});
$copy = static function (string $from, string $to) use (&$copy): void {
    if (is_dir($from)) {
        is_dir($to) || mkdir($to, 0700, true);
        foreach (scandir($from) ?: [] as $entry) {
            // never a database file, a cached manifest or an environment file
            if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.env')
                || str_contains($entry, '.sqlite') || str_ends_with($from, '/bootstrap/cache')) {
                continue;
            }
            $copy("{$from}/{$entry}", "{$to}/{$entry}");
        }
        return;
    }
    copy($from, $to) || throw new RuntimeException("cannot copy {$from}");
};
mkdir($work, 0700, true);
foreach (['app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'tests'] as $part) {
    $copy("{$lab}/{$part}", "{$work}/{$part}");
}
copy("{$lab}/phpunit.xml", "{$work}/phpunit.xml");
symlink("{$lab}/vendor", "{$work}/vendor") || $fail('cannot link the lab vendor folder');
foreach (['bootstrap/cache', 'storage/framework/views', 'storage/framework/cache/data',
    'storage/framework/sessions', 'storage/framework/testing', 'storage/logs'] as $dir) {
    is_dir("{$work}/{$dir}") || mkdir("{$work}/{$dir}", 0700, true);
}
// The lab may already hold the lesson's test, or an exercise attempt: this folder's files decide.
foreach (glob("{$work}/tests/Feature/SupportChatPage*Test.php") ?: [] as $old) {
    unlink($old);
}
// PHPUnit's bootstrap for the copy: the application classes come from the copy
// (before Composer's own map, which points at the lab), and so does the base path.
file_put_contents("{$work}/verify-bootstrap.php", '<?php
$_ENV["APP_BASE_PATH"] = $_SERVER["APP_BASE_PATH"] = ' . var_export($work, true) . ';
spl_autoload_register(static function (string $class): void {
    foreach ([
        "App\\\\" => "/app/",
        "Database\\\\Factories\\\\" => "/database/factories/",
        "Database\\\\Seeders\\\\" => "/database/seeders/",
        "Tests\\\\" => "/tests/",
    ] as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = ' . var_export($work, true) . ' . $dir . str_replace("\\\\", "/", substr($class, strlen($prefix))) . ".php";
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
}, true, true);
require ' . var_export("{$lab}/vendor/autoload.php", true) . ';
');
// One PHPUnit run on the copy. $only: a test file to run alone, or null for the whole suite.
// Returns [number of tests, list of failed test names, their messages joined].
$env = ['APP_KEY' => 'base64:' . base64_encode(random_bytes(32)), 'APP_BASE_PATH' => $work];
foreach (getenv() as $name => $value) {
    // the variables coding agents set would change the test output of some tools: a person has none
    if (preg_match('/^(AI_AGENT$|CLAUDE|CODEX_|CURSOR_AGENT$|APP_|DB_)/', $name) !== 1) {
        $env[$name] ??= $value;
    }
}
$phpunit = static function (?string $only) use ($lab, $work, $env, $fail): array {
    $junit = "{$work}/junit.xml";
    @unlink($junit);
    $command = [PHP_BINARY, "{$lab}/vendor/bin/phpunit", '--configuration', "{$work}/phpunit.xml",
        '--bootstrap', "{$work}/verify-bootstrap.php", '--do-not-cache-result', '--no-progress',
        '--colors=never', '--log-junit', $junit];
    $only === null || $command[] = $only;
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $work, $env);
    is_resource($process) || $fail('PHPUnit did not start');
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    proc_close($process);
    $xml = is_file($junit) ? simplexml_load_file($junit) : false;
    $xml !== false || $fail('PHPUnit wrote no result: ' . substr($output, -600));
    $failed = [];
    $messages = '';
    $cases = $xml->xpath('//testcase') ?: [];
    foreach ($cases as $case) {
        foreach ([...$case->failure, ...$case->error] as $problem) {
            $failed[] = (string) $case['name'];
            $messages .= (string) $problem . "\n";
        }
    }
    return [count($cases), array_values(array_unique($failed)), $messages];
};
$target = "{$work}/tests/Feature/" . TEST;
// ---- 2. Every typed stage: the whole suite, green, with the count read on camera.
foreach ([
    'stages/SupportChatPageTest.after-3b.php' => 7,
    'stages/SupportChatPageTest.after-4.php' => 8,
    'stages/SupportChatPageTest.after-5.php' => 8,
    'stages/SupportChatPageTest.after-6.php' => 9,
    'stages/SupportChatPageTest.after-7c.php' => 10,
    TEST => 11,
] as $stage => $expected) {
    copy(__DIR__ . "/{$stage}", $target);
    [$count, $failed, $messages] = $phpunit(null);
    ($count === $expected && $failed === [])
        || $fail("{$stage}: {$count} tests, failed: " . implode(', ', $failed) . ' ' . substr($messages, 0, 300));
    echo "stage {$stage}: {$count} tests passed" . PHP_EOL;
}
// A mutation: text replaced in one file of the copy, PHPUnit, the file restored.
$mutate = static function (string $file, array $pairs, ?string $only) use ($work, $phpunit, $fail): array {
    $path = "{$work}/{$file}";
    $source = (string) file_get_contents($path);
    $mutant = $source;
    foreach ($pairs as $old => $new) {
        substr_count($mutant, $old) === 1 || $fail("{$file} no longer holds exactly once: {$old}");
        $mutant = str_replace($old, $new, $mutant);
    }
    file_put_contents($path, $mutant);
    try {
        return $phpunit($only);
    } finally {
        file_put_contents($path, $source);
    }
};
$pageController = 'app/Http/Controllers/ConversationController.php';
$messageController = 'app/Http/Controllers/ConversationMessageController.php';
$policyLine = "        Gate::authorize('view', \$conversation);\n";
$validation = "        \$validated = \$request->validate([\n            'message' => ['required', 'string', 'max:2000'],\n        ]);\n";
// ---- 3. The red run of the lesson (scene 6): the suite at the nine-test stage, the page's policy line deleted.
copy(__DIR__ . '/stages/SupportChatPageTest.after-6.php', $target);
[$count, $failed, $messages] = $mutate($pageController, [$policyLine => "\n"], null);
($count === 9 && $failed === ['test_another_user_never_reaches_the_agent']
    && str_contains($messages, 'Expected response status code [403] but received 200.'))
    || $fail("the red run of scene 6 is not one failure of nine with 'received 200': {$count} tests, failed: "
        . implode(', ', $failed) . ' ' . substr($messages, 0, 300));
echo 'red run of the lesson: 1 of 9 failed, expected 403 but received 200' . PHP_EOL;
// ---- 4. Every test can fail. [file, replacements, the tests that must turn red (all the others stay green)].
copy(__DIR__ . '/' . TEST, $target);
$authGroup = "Route::middleware('auth')->group(function () {";
$messageRoute = "    Route::post(\n        '/conversations/{conversation}/messages',\n        ConversationMessageController::class,\n    )->name('conversations.messages.web');\n});";
$storeBinding = "\$this->app->singleton(MessageStoreInterface::class, fn () =>\n            new EloquentMessageStore(ChatMessage::class));";
$mutations = [
    'M1a every page outside the auth middleware' => ['routes/web.php',
        [$authGroup => 'Route::middleware([])->group(function () {'], [0]],
    'M1b the message route outside the auth group' => ['routes/web.php',
        [$messageRoute => "});\n" . substr($messageRoute, 0, -4)], [0]],
    'M1c the login route under another name' => ['routes/web.php',
        ["->name('login');" => "->name('signin');"], [0]],
    'M1d JSON errors only for the API paths' => ['bootstrap/app.php',
        ["\$request->is('api/*') || \$request->expectsJson()," => "\$request->is('api/*'),"], [0, 4]],
    'M1e the conversation page outside the auth group' => ['routes/web.php',
        ["            Route::get('/conversations/{conversation}', 'show')\n                ->name('conversations.show');\n" => '',
            "    )->name('conversations.messages.web');\n});" => "    )->name('conversations.messages.web');\n});\n"
                . "Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])\n"
                . "    ->name('conversations.show');"], [0]],
    'M2a transcript newest first' => ['app/Support/Transcript.php',
        ["->orderBy('id')" => "->orderByDesc('id')"], [1]],
    'M2b transcript without the assistant lines' => ['app/Support/Transcript.php',
        ["['user', 'assistant']" => "['user']"], [1]],
    'M3a the page without the policy (the red run on camera)' => [$pageController,
        [$policyLine => ''], [2]],
    'M3b the message action without the policy' => [$messageController,
        [$policyLine => ''], [2]],
    'M3c the policy checked after the agent ran' => [$messageController,
        [$policyLine => '', '        $answer = $state' => $policyLine . '        $answer = $state'], [2]],
    'M3d a policy that lets everybody in' => ['app/Policies/ConversationPolicy.php',
        ['return $conversation->user()->is($user);' => 'return true;'], [2]],
    'M4a an in-memory store for every resolution' => ['app/Providers/NeuronServiceProvider.php',
        [$storeBinding => "\$this->app->bind(MessageStoreInterface::class, fn () =>\n            new \\NeuronAI\\Chat\\History\\InMemoryMessageStore());"], [1, 3]],
    'M4b a thread of its own for every request' => [$messageController,
        ['->for($conversation->threadId())' => "->for(\$conversation->threadId() . ':' . uniqid())"], [3]],
    'M4c rows stored, history never loaded' => ['app/Providers/NeuronServiceProvider.php',
        ['new EloquentMessageStore(ChatMessage::class));' => 'new class(ChatMessage::class) extends EloquentMessageStore { public function loadActive(string $threadId): array { return []; } });'], [3]],
    'M4d the agent ignores the injected store' => ['app/Neuron/Agents/SupportAgent.php',
        ['        return $this->conversations;' => '        return new \\NeuronAI\\Chat\\History\\InMemoryMessageStore();'], [3]],
    'M4e the controller answers with a text of its own' => [$messageController,
        ["return response()->json(['answer' => \$answer]);" => "return response()->json(['answer' => 'Thanks!']);"], [3]],
    'M5a an empty message accepted as an empty text' => [$messageController,
        ["'message' => ['required', 'string', 'max:2000'],\n        ]);" => "'message' => ['nullable', 'string', 'max:2000'],\n        ]);\n        \$validated['message'] ??= '';"], [4]],
    'M5b no validation at all' => [$messageController,
        [$validation => "        \$validated = ['message' => (string) \$request->input('message')];\n"], [4]],
    'M5c validation after the agent ran' => [$messageController,
        [$validation => "        \$validated = ['message' => (string) \$request->input('message')];\n",
            '        $answer = $state' => $validation . '        $answer = $state'], [4]],
];
$killed = array_fill(0, count(NAMES), 0);
foreach ($mutations as $name => [$file, $pairs, $red]) {
    [$count, $failed, $messages] = $mutate($file, $pairs, $target);
    $expected = array_map(static fn (int $i): string => NAMES[$i], $red);
    sort($failed);
    sort($expected);
    ($count === count(NAMES) && $failed === $expected)
        || $fail("{$name}: expected red " . implode(', ', $expected) . ', got ' . (implode(', ', $failed) ?: 'none'));
    foreach ($red as $i) {
        $killed[$i]++;
    }
    // the first line of every failure, for the evidence table of script.md
    $reasons = array_values(array_filter(
        explode("\n", $messages),
        static fn (string $line): bool => preg_match('/^(Expected response|Failed asserting|[\w\\\\]+(Exception|Error): )/', $line) === 1,
    ));
    echo "mutation {$name}: red " . implode(', ', $expected) . PHP_EOL
        . '    ' . substr(implode(' / ', array_slice($reasons, 0, 2)), 0, 150) . PHP_EOL;
}
min($killed) > 0 || $fail('a test was never seen failing');
// The copy is the lab's code again: the five tests pass.
[$count, $failed] = $phpunit($target);
($count === 5 && $failed === []) || $fail('the five tests do not pass after the mutations were undone');
// ---- 5. The exercise solution: green, and both of its tests can fail.
$exercise = "{$work}/tests/Feature/SupportChatPageExerciseTest.php";
copy(__DIR__ . '/exercise/SupportChatPageExerciseTest.php', $exercise);
[$count, $failed, $messages] = $phpunit($exercise);
($count === 2 && $failed === []) || $fail('the exercise solution fails: ' . substr($messages, 0, 300));
[, $failed] = $mutate($messageController, ["                502,\n" => "                200,\n"], $exercise);
$failed === ['test_an_empty_answer_becomes_a_502'] || $fail('exercise: a 200 for an empty answer is not caught');
[, $failed] = $mutate($messageController, ['max:2000' => 'max:5000'], $exercise);
$failed === ['test_a_too_long_message_never_calls_the_model'] || $fail('exercise: a longer limit is not caught');
[$count, $failed] = $phpunit(null);
($count === 13 && $failed === []) || $fail('the suite with the exercise solution is not 13 green tests');
echo 'TESTING THE CHAT APP OK' . PHP_EOL;
