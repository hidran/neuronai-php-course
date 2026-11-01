<?php
declare(strict_types=1);
// Offline check of "Configuration and environments" (no model call, no keys).
// Usage: php verify-configuration.php <lab>
// <lab> is the Laravel lab at the end of the lesson: config/neuron.php published, the
// NeuronAI block in .env.example, the ai:status command in routes/console.php. The
// lab's routes/console.php and .env.example must equal this folder's files.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use NeuronAI\Laravel\AIProviderManager;
$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('usage: verify-configuration.php <lab>');
// The subprocesses get the environment of this script before Laravel loads the lab's .env.
$baseEnv = getenv();
$fail = static function (string $what): never {
    throw new RuntimeException($what);
};
$same = static fn (string $a, string $b): bool => rtrim((string) file_get_contents($a), "\n") === rtrim((string) file_get_contents($b), "\n");
// 1. The lesson's files are in the lab; the published configuration is the SDK's own.
if (!$same($lab . '/routes/console.php', __DIR__ . '/console.php')) {
    $fail('routes/console.php differs from files/console.php');
}
if (!$same($lab . '/.env.example', __DIR__ . '/env.example')) {
    $fail('.env.example differs from files/env.example');
}
if (!$same($lab . '/config/neuron.php', $lab . '/vendor/neuron-core/neuron-laravel/config/neuron.php')) {
    $fail('config/neuron.php is not the published SDK configuration');
}
if (is_dir($lab . '/database/migrations/neuron')) {
    $fail('the migrations were published too: the lesson publishes configuration only');
}
// 2. .env.example documents names and placeholders, never a credential.
foreach (file($lab . '/.env.example', FILE_IGNORE_NEW_LINES) ?: [] as $row) {
    if (preg_match('/^[A-Z_]*(KEY|SECRET|TOKEN)=(.+)$/', $row, $m) === 1 && $m[2] !== 'null') {
        $fail(".env.example holds a value for a secret: {$m[0]}");
    }
}
// 3. The configuration contract the narration reads (segments 2b and 3b).
$config = (string) file_get_contents($lab . '/config/neuron.php');
foreach ([
    "'default' => env('NEURON_AI_PROVIDER'),",
    "'default' => env('NEURON_EMBEDDING_PROVIDER'),",
    "'default' => env('NEURON_STORE_PROVIDER', 'file'),",
    "'key' => env('ANTHROPIC_KEY'),",
] as $fact) {
    if (!str_contains($config, $fact)) {
        $fail("config/neuron.php no longer has: {$fact}");
    }
}
// 4. Boot the application with a known configuration (nothing is read from .env here).
require $lab . '/vendor/autoload.php';
$app = require $lab . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config()->set('neuron.provider.anthropic', ['key' => 'secret-never-printed', 'model' => 'claude-offline', 'parameters' => []]);
// 5. A missing provider setting: the manager fails with a type error (segment 2b).
config()->set('neuron.provider.default', null);
$manager = new AIProviderManager($app);
try {
    $manager->driver();
    $fail('the provider manager accepted a missing default');
} catch (TypeError) {
}
// 6. ai:status rejects an empty selector with a useful message, before any provider exists.
foreach ([null, ''] as $empty) {
    config()->set('neuron.provider.default', $empty);
    $code = Artisan::call('ai:status');
    if ($code === 0 || !str_contains(Artisan::output(), 'Set NEURON_AI_PROVIDER first.')) {
        $fail('ai:status did not reject an empty provider: ' . Artisan::output());
    }
}
// 7. With a provider it prints only provider and model: no credential, no array dump.
config()->set('neuron.provider.default', 'anthropic');
$code = Artisan::call('ai:status');
$out = trim(Artisan::output());
if ($code !== 0 || $out !== 'Provider: anthropic, model: claude-offline') {
    $fail("ai:status printed: {$out}");
}
if (str_contains($out, 'secret-never-printed')) {
    $fail('ai:status printed a credential');
}
// 8. The command-line paths of segments 4b, 5 and 5b, each in its own process: the process
//    environment wins over .env (no file is read here), the configuration cache keeps the
//    same answer, --env=staging loads .env.staging when the lab has it.
$php = PHP_BINARY;
$run = static function (string $command, array $env = []) use ($lab, $baseEnv): array {
    $proc = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $lab, $env + $baseEnv);
    $out = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    return [proc_close($proc), trim($out)];
};
$env = ['NEURON_AI_PROVIDER' => 'gemini', 'GEMINI_MODEL' => 'gemini-offline'];
[$code, $out] = $run("{$php} artisan ai:status --no-ansi", $env);
if ($code !== 0 || $out !== 'Provider: gemini, model: gemini-offline') {
    $fail("artisan ai:status printed: {$out}");
}
if (is_file($lab . '/bootstrap/cache/config.php')) {
    $fail('the lab already has a configuration cache: clear it first');
}
[$code] = $run("{$php} artisan config:cache --no-ansi", $env);
[, $cached] = $run("{$php} artisan ai:status --no-ansi", ['NEURON_AI_PROVIDER' => 'ollama']);
[$cleared] = $run("{$php} artisan config:clear --no-ansi");
if ($code !== 0 || $cleared !== 0 || $cached !== 'Provider: gemini, model: gemini-offline') {
    $fail("with a cached configuration ai:status printed: {$cached}");
}
if (is_file($lab . '/.env.staging')) {
    [$code, $out] = $run("{$php} artisan ai:status --env=staging --no-ansi");
    if ($code !== 0 || !str_starts_with($out, 'Provider: openai, model: ')) {
        $fail("--env=staging printed: {$out}");
    }
}
echo "CONFIGURATION OK\n";
