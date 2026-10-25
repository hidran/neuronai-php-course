<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/AssistantAgent.php';
use App\Agents\AssistantAgent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
$fake = new FakeAIProvider(new AssistantMessage('It rebuilds the class map.'));
$agent = AssistantAgent::make($fake)->setThreadId('t');
$state = $agent->chat(new UserMessage('What does dump-autoload do?'));
if ($state->getMessage()?->getContent() !== 'It rebuilds the class map.') {
    throw new RuntimeException('answer not returned');
}
if (!$agent->getInstructions() instanceof SystemMessage) {
    throw new RuntimeException('instructions must be a SystemMessage');
}
$text = (string) $agent->getInstructions()->getContent();
if (!str_contains($text, '# IDENTITY AND PURPOSE')
    || !str_contains($text, 'friendly assistant')) {
    throw new RuntimeException("prompt: {$text}");
}
echo 'ASSISTANT OK' . PHP_EOL;
