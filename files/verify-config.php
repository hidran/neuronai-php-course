<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
$config = __DIR__ . '/../../01-05-keys-and-environment/files/';
require $config . 'Provider.php';
require $config . 'Settings.php';
require __DIR__ . '/ProviderFactory.php';
use App\Config\ProviderFactory;
use App\Config\Settings;
$cases = [
    ['NEURON_PROVIDER' => 'anthropic', 'ANTHROPIC_KEY' => 'k', 'ANTHROPIC_MODEL' => 'm'],
    ['NEURON_PROVIDER' => 'openai', 'OPENAI_KEY' => 'k', 'OPENAI_MODEL' => 'm'],
    ['NEURON_PROVIDER' => 'gemini', 'GEMINI_KEY' => 'k', 'GEMINI_MODEL' => 'm'],
    ['NEURON_PROVIDER' => 'ollama', 'OLLAMA_MODEL' => 'm', 'OLLAMA_URL' => 'http://x/api'],
];
foreach ($cases as $env) {
    $s = Settings::fromEnv($env);
    $p = new ProviderFactory($s)->create();
    echo $s->provider->value . ' => ' . $p::class . PHP_EOL;
}
try {
    Settings::fromEnv(['NEURON_PROVIDER' => 'anthropic']);
    echo 'FAIL: missing key accepted' . PHP_EOL;
    exit(1);
} catch (RuntimeException $e) {
    echo 'missing key: ' . $e->getMessage() . PHP_EOL;
}
try {
    Settings::fromEnv(['NEURON_PROVIDER' => 'bogus']);
    exit(1);
} catch (RuntimeException $e) {
    echo 'bad name: ' . $e->getMessage() . PHP_EOL;
}
echo 'CONFIG OK' . PHP_EOL;
