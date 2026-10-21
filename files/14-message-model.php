<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$question = new UserMessage('What does this line return?');
$question->addContent(
    new TextContent("str_contains('NeuronAI', 'AI')"),
);
echo 'Question: ' . $question->getContent() . PHP_EOL;
foreach ($question->getContentBlocks() as $block) {
    echo "Block: {$block->getType()->value}" . PHP_EOL;
}
echo 'JSON: ' . json_encode($question) . PHP_EOL;
$example = new AssistantMessage(implode(PHP_EOL, [
    'Topic: arrays, array_sum',
    'Summary: It returns 5, the sum of the values.',
    'Example: echo array_sum([2, 3]); // 5',
]));
$history = [
    new UserMessage('What does this line return? array_sum([2, 3])'),
    $example,
    $question,
];
$state = AssistantAgent::make()
    ->setThreadId('messages')
    ->chat($history);
$answer = $state->getMessage();
if (!$answer instanceof AssistantMessage) {
    exit('No answer.' . PHP_EOL);
}
echo $answer->getContent() . PHP_EOL;
foreach ($answer->getContentBlocks() as $block) {
    echo "Answer block: {$block->getType()->value}" . PHP_EOL;
}
echo "Stop: {$answer->stopReason()}" . PHP_EOL;
echo 'Usage: ' . json_encode($answer->getUsage()) . PHP_EOL;
$keys = array_keys($answer->jsonSerialize());
echo 'Answer keys: ' . implode(', ', $keys) . PHP_EOL;