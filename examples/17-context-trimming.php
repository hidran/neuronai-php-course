<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\HelloAgent;
use NeuronAI\Chat\History\InMemoryMessageStore;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$window = 700;
$store = new InMemoryMessageStore();
$agent = HelloAgent::make($provider)
    ->setMessageStore($store)
    ->setThreadId('alice')
    ->setContextWindow($window);
$questions = [
    'Hi, my name is Alice and I love PHP.',
    'Which version of neuron-core/neuron-ai is installed?',
    'What is a readonly class?',
    'What is my name?',
];
foreach ($questions as $number => $question) {
    $reply = $agent->chat(new UserMessage($question))->getMessage();
    echo 'Answer: ' . $reply?->getContent() . PHP_EOL;
    $history = $agent->getChatHistory();
    $kept = $history->getMessages();
    $stored = count($store->loadAll('alice'));
    $tokens = $history->calculateTotalUsage();
    echo 'Turn ' . ($number + 1) . ': kept ' . count($kept)
        . " of {$stored} messages, {$tokens} of {$window} tokens"
        . PHP_EOL;
    $oldest = array_first($kept)?->getContent() ?? '(none)';
    echo "Oldest kept: {$oldest}" . PHP_EOL;
}
