<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\MonitoringSettings;
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\HelloAgent;
use Dotenv\Dotenv;
use Inspector\Neuron\V4\InspectorSubscriber;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Observability\ObservabilityEvent;
Dotenv::createImmutable(dirname(__DIR__))->load();
$settings = Settings::fromEnv($_ENV);
$monitoring = MonitoringSettings::fromEnv($_ENV);
$provider = new ProviderFactory($settings)->create();
$inspector = InspectorSubscriber::instance(
    key: $monitoring->ingestionKey, transport: 'sync',
);
$agent = HelloAgent::make($provider)->setThreadId('hello')
    ->subscribe(ObservabilityEvent::class, $inspector);
$state = $agent->chat(
    new UserMessage('What is an AI agent, in plain words?')
);
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
echo 'Trace name in Inspector: ' . HelloAgent::class . PHP_EOL;
