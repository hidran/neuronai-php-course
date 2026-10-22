<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\StreamingAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$message = new UserMessage('Explain why PHP uses namespaces.');
$agent = StreamingAgent::make()->setThreadId('stream-demo');
$started = microtime(true);
$stream = $agent->stream($message);
echo 'Stream value: ' . $stream::class . PHP_EOL;
$firstText = null;
$parts = 0;
$received = '';
foreach ($stream as $chunk) {
    if (!$chunk instanceof TextChunk || $chunk->content === '') {
        continue;
    }
    $firstText ??= microtime(true) - $started;
    $parts++;
    $received .= $chunk->content;
    if ($parts <= 3) {
        echo 'Fragment ' . $parts . ': '
            . json_encode($chunk->content) . PHP_EOL;
    }
}
$state = $stream->getReturn();
$final = $state->getMessage()?->getContent() ?? '';
$elapsed = microtime(true) - $started;
if ($firstText === null || $received === '' || $final !== $received) {
    throw new RuntimeException('Text assembly check failed.');
}
echo 'First text ms: ' . round($firstText * 1000) . PHP_EOL;
echo 'Finished ms: ' . round($elapsed * 1000) . PHP_EOL;
echo 'Text fragments: ' . $parts . PHP_EOL;
echo 'Final state: ' . $state::class . PHP_EOL;
echo 'Final text bytes: ' . strlen($final) . PHP_EOL;
echo 'Fragments match final: yes' . PHP_EOL;
echo 'Stream inspection ' . 'complete.' . PHP_EOL;
