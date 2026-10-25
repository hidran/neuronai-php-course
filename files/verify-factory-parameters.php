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
$env = ['NEURON_PROVIDER' => 'anthropic', 'ANTHROPIC_KEY' => 'k', 'ANTHROPIC_MODEL' => 'm'];
$factory = new ProviderFactory(Settings::fromEnv($env));
$p = $factory->create(['temperature' => 0]);
$ref = new ReflectionProperty($p, 'parameters');
if ($ref->getValue($p) !== ['temperature' => 0]) {
    throw new RuntimeException('parameters not forwarded');
}
foreach (['openai', 'gemini', 'ollama'] as $name) {
    $e = ['NEURON_PROVIDER' => $name, strtoupper($name) . '_KEY' => 'k', strtoupper($name) . '_MODEL' => 'm'];
    $q = new ProviderFactory(Settings::fromEnv($e))->create(['temperature' => 0.2]);
    $r = new ReflectionProperty($q, 'parameters');
    if ($r->getValue($q) !== ['temperature' => 0.2]) {
        throw new RuntimeException("{$name}: parameters not forwarded");
    }
}
echo 'FACTORY PARAMETERS OK' . PHP_EOL;
