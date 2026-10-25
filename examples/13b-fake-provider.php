<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
$fake = new FakeAIProvider(
    new AssistantMessage('Topic: tests. Summary: offline. Example: ok'),
);
$agent = AssistantAgent::make($fake)->setThreadId('fake');
$start = hrtime(true);
$state = $agent->chat(new UserMessage('Any question works here.'));
$ms = intdiv(hrtime(true) - $start, 1_000_000);
echo $state->getMessage()?->getContent() . PHP_EOL;
echo "Answered by: fake, {$fake->getModel()}, {$ms} ms" . PHP_EOL;
echo 'Provider calls recorded: ' . $fake->getCallCount() . PHP_EOL;
