<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Config\ProviderFactory;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$question = $argv[1] ?? 'What does composer dump-autoload do?';
$agent = AssistantAgent::make($provider)->setThreadId('assistant');
$state = $agent->chat(new UserMessage($question));
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
echo 'Answered by: ' . $settings->provider->value . PHP_EOL;
