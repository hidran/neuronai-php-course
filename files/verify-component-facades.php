<?php
declare(strict_types=1);
// Offline check of the lesson (no model call, no network).
// Usage: php verify-component-facades.php <lab>
// <lab> is the Laravel lab of the section (neuron-laravel 2.x, NeuronAI 4.1).
// Each run of the diagnostic happens in a child process that loads this
// folder's classes before the lab's: once with the service provider of the
// lesson's start (the three bindings, no singleton) and once with the fix.
use App\Neuron\Agents\SupportDocsAgent;
use Illuminate\Contracts\Console\Kernel;
use NeuronAI\Laravel\Facades\AIProvider;
use NeuronAI\Laravel\Facades\EmbeddingProvider;
use NeuronAI\Laravel\Facades\VectorStore;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use Symfony\Component\Console\Output\BufferedOutput;
$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('lab path');
$mode = $argv[2] ?? null;
foreach ([
    'NEURON_AI_PROVIDER' => 'anthropic',
    'ANTHROPIC_KEY' => 'offline-check',
    'NEURON_EMBEDDING_PROVIDER' => 'ollama',
    'LOG_CHANNEL' => 'null',
] as $name => $value) {
    putenv("{$name}={$value}");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}
if ($mode !== null) {
    // Child: one artisan run with the chosen service provider.
    require $lab . '/vendor/autoload.php';
    require __DIR__ . ($mode === 'after' ? '' : '/start') . '/AppServiceProvider.php';
    require __DIR__ . '/start/Components.php';
    require __DIR__ . '/start/SupportDocsAgent.php';
    $app = require $lab . '/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();
    if ($mode === 'agent') {
        // The retrieval agent receives its three services from the container,
        // bound by our service provider to the managers' default drivers.
        $agent = app(SupportDocsAgent::class);
        $hooks = array_filter(['provider', 'embeddings', 'vectorStore'], fn (string $hook) =>
            new ReflectionMethod(SupportDocsAgent::class, $hook)->class === SupportDocsAgent::class);
        $ok = $hooks === []
            && $agent->getProvider() === AIProvider::driver()
            && $agent->resolveEmbeddingsProvider() === EmbeddingProvider::driver()
            && $agent->resolveVectorStore() instanceof VectorStoreInterface
            && VectorStore::getDefaultDriver() === 'file';
        // Scene 3b: a contextual binding gives one agent its own provider object,
        // built from a configuration group, while the others keep the shared driver.
        app()->when(SupportDocsAgent::class)->needs(AIProviderInterface::class)
            ->give(fn () => new Anthropic(...config('neuron.provider.anthropic')));
        $own = app(SupportDocsAgent::class)->getProvider();
        $ok = $ok && $own instanceof Anthropic && $own !== AIProvider::driver()
            && app(AIProviderInterface::class) === AIProvider::driver();
        echo $ok ? 'AGENT SERVICES OK' : 'AGENT SERVICES FAILED', PHP_EOL;
        exit(0);
    }
    $output = new BufferedOutput();
    $kernel->call('components', ['check' => $mode === 'provider' ? 'provider' : 'vector'], $output);
    echo $output->fetch();
    exit(0);
}
// 1. What the narration reads in the installed SDK.
$sdk = $lab . '/vendor/neuron-core/neuron-laravel/src';
$sp = file_get_contents($sdk . '/NeuronAIServiceProvider.php');
if (!str_contains($sp, '$this->app->singleton(AIProviderManager::class);')
    || !str_contains($sp, '$this->app->singleton(EmbeddingProviderManager::class);')
    || str_contains($sp, 'VectorStoreManager')) {
    throw new RuntimeException('the SDK registers its managers differently now: re-check scenes 5 and 5b');
}
$manager = file_get_contents($sdk . '/AIProviderManager.php');
if (!str_contains($manager, "return config('neuron.provider.default');")
    || !str_contains($manager, "return new Anthropic(...config('neuron.provider.anthropic'));")) {
    throw new RuntimeException('AIProviderManager changed: re-check scene 2b');
}
$run = static function (string $mode) use ($lab): string {
    return (string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' '
        . escapeshellarg($lab) . ' ' . escapeshellarg($mode) . ' 2>&1');
};
// 2. The provider driver is cached by its manager.
$out = $run('provider');
foreach (['Driver: anthropic', 'Class: NeuronAI\Providers\Anthropic\Anthropic', 'Same object: true'] as $line) {
    if (!str_contains($out, $line)) {
        throw new RuntimeException("provider check: {$out}");
    }
}
// 3. Without the singleton the container's manager misses the facade's extension.
$out = $run('before');
if (!str_contains($out, 'Facade: NeuronAI\RAG\VectorStore\MemoryVectorStore')
    || !str_contains($out, 'Container: Driver [memory] not supported.')) {
    throw new RuntimeException("vector check before the fix: {$out}");
}
// 4. With the singleton both paths see the driver.
$out = $run('after');
if (!str_contains($out, 'Container: NeuronAI\RAG\VectorStore\MemoryVectorStore')) {
    throw new RuntimeException("vector check after the fix: {$out}");
}
// 5. The retrieval agent's constructor injection and a contextual binding.
$out = $run('agent');
if (trim($out) !== 'AGENT SERVICES OK') {
    throw new RuntimeException("retrieval agent services: {$out}");
}
echo 'COMPONENT FACADES OK' . PHP_EOL;
