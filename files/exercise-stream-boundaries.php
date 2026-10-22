<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\StreamingAgent;
use Dotenv\Dotenv;
use NeuronAI\Agent\AgentState;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\UserMessage;
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function collectText(iterable $events): array
{
    $text = '';
    $count = 0;
    foreach ($events as $event) {
        if (!$event instanceof TextChunk || $event->content === '') {
            continue;
        }
        $text .= $event->content;
        $count++;
    }
    return [$text, $count];
}
$cases = [
    ['split word', ['name', 'space', 's'], 'namespaces'],
    ['whitespace', [' Leading', ' ', 'space', "\n"], " Leading space\n"],
    ['UTF-8', ['caf', 'é', ' ☕'], 'café ☕'],
];
foreach ($cases as [$name, $parts, $expected]) {
    $events = [(object) ['content' => 'ignore this']];
    $events[] = new TextChunk('test', '');
    foreach ($parts as $part) {
        $events[] = new TextChunk('test', $part);
    }
    [$actual, $count] = collectText($events);
    check($actual === $expected, $name . ': changed fragment text');
    check($count === count($parts), $name . ': wrong text count');
    echo 'Boundary case passed: ' . $name . PHP_EOL;
}
Dotenv::createImmutable(dirname(__DIR__))->load();
$stream = StreamingAgent::make()
    ->setThreadId('boundary-exercise')
    ->stream(new UserMessage('Explain a PHP namespace collision.'));
check($stream instanceof Generator, 'Expected a generator.');
$unavailable = false;
try {
    $stream->getReturn();
} catch (Exception) {
    $unavailable = true;
}
check($unavailable, 'An unstarted stream has no final state.');
[$received, $count] = collectText($stream);
$state = $stream->getReturn();
check($state instanceof AgentState, 'Expected AgentState.');
check($count > 0 && $received !== '', 'Expected nonempty text.');
check(
    $received === $state->getMessage()?->getContent(),
    'Live fragments differ from the complete message.',
);
echo 'Live fragments equal final message: yes' . PHP_EOL;
echo 'Early getReturn rejected: yes' . PHP_EOL;
echo 'Boundary exercise complete.' . PHP_EOL;
