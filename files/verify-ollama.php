<?php
declare(strict_types=1);
// Offline check, not typed on screen: NEURON_PROVIDER=ollama builds the
// Ollama provider from configuration alone, with no key, the url default
// and the model from OLLAMA_MODEL; HelloAgent receives it unchanged.
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
$lessons = __DIR__ . '/../../';
require $lessons . '01-05-keys-and-environment/files/Provider.php';
require $lessons . '01-05-keys-and-environment/files/Settings.php';
require $lessons . '01-06-hello-neuronai/files/ProviderFactory.php';
require $lessons . '01-06-hello-neuronai/files/HelloAgent.php';
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\HelloAgent;
use NeuronAI\Providers\Ollama\Ollama;
$base = static fn (Ollama $p): string
    => new ReflectionProperty($p, 'baseUri')->getValue($p);
$cases = [
    [['NEURON_PROVIDER' => 'ollama', 'OLLAMA_MODEL' => 'qwen2.5:7b'],
        'http://localhost:11434/api', 'qwen2.5:7b'],
    [['NEURON_PROVIDER' => 'ollama', 'OLLAMA_MODEL' => 'qwen2.5:3b',
        'OLLAMA_URL' => 'http://gpu-box:11434/api'],
        'http://gpu-box:11434/api', 'qwen2.5:3b'],
];
foreach ($cases as [$env, $url, $model]) {
    $settings = Settings::fromEnv($env);
    if ($settings->key !== null) {
        throw new RuntimeException('Ollama must not need a key');
    }
    $provider = new ProviderFactory($settings)->create();
    if (!$provider instanceof Ollama) {
        throw new RuntimeException('expected Ollama, got ' . $provider::class);
    }
    if ($base($provider) !== $url || $provider->getModel() !== $model) {
        throw new RuntimeException("wrong url or model for {$model}");
    }
    $agent = HelloAgent::make($provider);
    if ($agent->getProvider() !== $provider) {
        throw new RuntimeException('HelloAgent did not keep the provider');
    }
    echo "ollama => {$url} {$model}" . PHP_EOL;
}
try {
    Settings::fromEnv(['NEURON_PROVIDER' => 'ollama']);
    throw new LogicException('missing OLLAMA_MODEL accepted');
} catch (RuntimeException $e) {
    echo 'missing model: ' . $e->getMessage() . PHP_EOL;
}
echo 'OLLAMA OK' . PHP_EOL;
