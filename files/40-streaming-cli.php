<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\StreamingAgent;
use App\Config\ProviderFactory;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$prompt = $argv[1]
    ?? 'Explain dependency injection for a PHP developer.';
echo 'You: ' . $prompt . PHP_EOL;
echo 'Assistant: ';
flush();
$start = hrtime(true);
$first = null;
$stream = StreamingAgent::make($provider)->setThreadId('cli')
    ->stream(new UserMessage($prompt));
foreach ($stream as $chunk) {
    if (!$chunk instanceof TextChunk || $chunk->content === '') {
        continue;
    }
    $first ??= hrtime(true);
    echo $chunk->content;
    flush();
}
$end = hrtime(true);
$state = $stream->getReturn();
$reply = $state->getMessage()?->getContent() ?? '';
$firstText = $first === null
    ? 'none'
    : sprintf('%.2fs', ($first - $start) / 1e9);
printf(
    "\n\nFirst text: %s | Total: %.2fs\n",
    $firstText,
    ($end - $start) / 1e9,
);
echo 'Final text: ' . mb_strlen($reply) . ' characters' . PHP_EOL;
echo 'CLI stream ' . 'complete.' . PHP_EOL;
