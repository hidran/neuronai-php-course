<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/StreamingAgent.php';
use App\Agents\StreamingAgent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
$answer = 'Namespaces keep class names from clashing.';
$fake = new FakeAIProvider(new AssistantMessage($answer));
$fake->setStreamChunkSize(7);
$stream = StreamingAgent::make($fake)->setThreadId('t')
    ->stream(new UserMessage('Why namespaces?'));
if (!$stream instanceof Generator) {
    throw new RuntimeException('stream() must return a Generator');
}
$received = '';
$parts = 0;
foreach ($stream as $chunk) {
    if ($chunk instanceof TextChunk) {
        $received .= $chunk->content;
        $parts++;
    }
}
$state = $stream->getReturn();
if ($parts < 2 || $received !== $answer
    || $state->getMessage()?->getContent() !== $answer) {
    throw new RuntimeException("assembly failed: {$received}");
}
if ($fake->getRecorded()[0]->method !== 'stream') {
    throw new RuntimeException('provider was not streamed');
}
echo 'STREAM OK' . PHP_EOL;
