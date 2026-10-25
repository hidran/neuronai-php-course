<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Config\ProviderFactory;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$agent = AssistantAgent::make($provider)->setThreadId('context');
$questions = [
    'What time is it?',
    'And now? Which NeuronAI version is installed?',
];
foreach ($questions as $question) {
    $state = $agent->chat(new UserMessage($question));
    echo "You: {$question}" . PHP_EOL;
    echo $state->getMessage()?->getContent() . PHP_EOL;
    sleep(1);
}
foreach ($agent->getChatHistory()->getMessages() as $message) {
    if ($message instanceof UserMessage) {
        echo 'Stored: ' . $message->getContent() . PHP_EOL;
    }
}
