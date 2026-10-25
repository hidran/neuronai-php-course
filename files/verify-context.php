<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/AssistantAgent.php';
use App\Agents\AssistantAgent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
$fake = new FakeAIProvider(new AssistantMessage('ok'));
$agent = AssistantAgent::make($fake)->setThreadId('t');
$agent->chat(new UserMessage('What time is it?'));
$sent = $fake->getRecorded()[0]->messages ?? [];
$all = implode(' ', array_map(fn ($m) => (string) $m->getContent(), $sent));
if (!str_contains($all, 'Now: ') || !str_contains($all, 'UTC')) {
    throw new RuntimeException("context missing: {$all}");
}
if (!str_contains($all, 'Installed NeuronAI: 4.')) {
    throw new RuntimeException('version missing');
}
$stored = $agent->getChatHistory()->getMessages();
foreach ($stored as $m) {
    if ($m instanceof UserMessage && str_contains((string) $m->getContent(), 'Now: ')) {
        throw new RuntimeException('context leaked into history');
    }
}
echo 'CONTEXT OK' . PHP_EOL;
