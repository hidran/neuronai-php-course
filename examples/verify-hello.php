<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/HelloAgent.php';
use App\HelloAgent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
$fake = new FakeAIProvider(new AssistantMessage('An agent is a loop.'));
$state = HelloAgent::make($fake)->setThreadId('t')
    ->chat(new UserMessage('What is an agent?'));
$text = $state->getMessage()?->getContent();
if ($text !== 'An agent is a loop.') {
    throw new RuntimeException("unexpected answer: {$text}");
}
if ($fake->getCallCount() !== 1) {
    throw new RuntimeException('expected one provider call');
}
$prompt = (string) HelloAgent::make($fake)->getInstructions()->getContent();
if (!str_contains($prompt, 'friendly PHP teacher')
    || !str_contains($prompt, 'two short sentences')) {
    throw new RuntimeException("unexpected prompt: {$prompt}");
}
echo 'HELLO OK' . PHP_EOL;
