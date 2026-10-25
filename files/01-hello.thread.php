<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$settings = Settings::fromEnv($_ENV);
$provider = new ProviderFactory($settings)->create();
$agent = HelloAgent::make($provider)->setThreadId('hello');
$state = $agent->chat(
    new UserMessage('What is an AI agent, in plain words?')
);
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
$state = $agent->chat(
    new UserMessage('Now say it again in five words.')
);
echo 'Follow-up: ' . $state->getMessage()?->getContent() . PHP_EOL;
$messages = $agent->getChatHistory()->getMessages();
echo 'Messages in the thread: ' . count($messages) . PHP_EOL;
echo 'Answered by: ' . $settings->provider->value . PHP_EOL;
