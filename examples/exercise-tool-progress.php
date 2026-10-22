<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\ServerAgent;
use App\Streaming\StreamProgress;
use App\Streaming\ToolStreamConsumer;
use Dotenv\Dotenv;
use NeuronAI\Agent\AgentState;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolArgumentChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolCallChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolResultChunk;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\ProviderResponse;
use NeuronAI\Tools\ToolCall;
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function replay(array $events, AgentState $state): Generator
{
    yield from $events;
    return $state;
}
function capture(Generator $events, StreamProgress $view): array
{
    ob_start();
    try {
        $state = ToolStreamConsumer::consume($events, $view);
        return [$state, ob_get_contents()];
    } finally {
        ob_end_clean();
    }
}
$state = new AgentState();
$state->setResponse(new ProviderResponse(new AssistantMessage('Ready.')));
$state->incrementToolRun('fixture');
$state->incrementToolRun('fixture');
$a = new ToolCall('internal_fixture', 'a', ['secret' => 'SENTINEL']);
$b = new ToolCall('internal_fixture', 'b', ['secret' => 'SENTINEL']);
$a->setResult('RESULT_SENTINEL');
$b->setResult('RESULT_SENTINEL');
$view = new StreamProgress(diagnostic: false);
$events = [
    new TextChunk('intro', 'I will check. '),
    new ToolArgumentChunk('intro', 'internal_fixture', '{"sec', 'a'),
    new ToolCallChunk('batch', $a),
    new ToolCallChunk('batch', $b),
    new ToolResultChunk($b),
    new ToolResultChunk($a),
    (object) ['content' => 'IMPOSTOR_SENTINEL'],
    new TextChunk('final', 'Ready.'),
];
[$result, $output] = capture(replay($events, $state), $view);
check($result === $state, 'Completed replay was rejected.');
check(array_keys($view->completed()) === ['b', 'a'], 'Lost IDs.');
check(substr_count($output, 'Working on your request') === 2,
    'Expected the generic fallback for both invocations.');
foreach (['SENTINEL', 'internal_fixture', '{"sec'] as $hidden) {
    check(!str_contains($output, $hidden), 'Raw progress leaked.');
}
echo 'Repeated name and reverse results: passed' . PHP_EOL;
echo 'Unknown names and raw payload policy: passed' . PHP_EOL;
$broken = (function (): Generator {
    yield new TextChunk('partial', 'Only half');
    throw new RuntimeException('PRIVATE_PROVIDER_DETAIL');
})();
[$result, $output] = capture($broken, new StreamProgress(false));
check($result === null, 'Incomplete run returned a state.');
check(str_contains($output, 'Only half'), 'Partial text disappeared.');
check(str_contains($output, 'The answer did not finish.'),
    'Missing incomplete-run notice.');
check(!str_contains($output, 'Tool stream complete.'),
    'Incomplete run claimed success.');
check(!str_contains($output, 'PRIVATE_PROVIDER_DETAIL'),
    'Raw error details leaked.');
echo 'Partial failure without false success: passed' . PHP_EOL;
$bad = new ToolCall('internal_fixture', 'missing');
$bad->setResult('RESULT_SENTINEL');
[$result] = capture(replay([new ToolResultChunk($bad)], $state),
    new StreamProgress(false));
check($result === null, 'Unmatched result was accepted.');
echo 'Unmatched result rejected: passed' . PHP_EOL;
Dotenv::createImmutable(dirname(__DIR__))->load();
$view = new StreamProgress(diagnostic: false);
$stream = ServerAgent::make()->setThreadId('two-server-exercise')
    ->stream(new UserMessage('Use the tool once for training and '
        . 'once for backup. Give both server addresses.'));
$state = ToolStreamConsumer::consume($stream, $view);
check($state instanceof AgentState, 'Live stream failed.');
check(count($view->completed()) === 2, 'Expected two call IDs.');
check(count(array_unique(array_values($view->completed()))) === 1,
    'Expected the same capability for both calls.');
$text = $state->getMessage()?->getContent() ?? '';
check(str_contains($text, '192.0.2.42'), 'Training address missing.');
check(str_contains($text, '192.0.2.43'), 'Backup address missing.');
echo 'Two live invocations have distinct IDs: passed' . PHP_EOL;
echo 'Tool progress exercise complete.' . PHP_EOL;
