<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\Anthropic\Anthropic;
Dotenv::createImmutable(dirname(__DIR__))->load();
$model = new Anthropic(
    key: $_ENV['ANTHROPIC_KEY'],
    model: $_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-5-5',
);
$model->systemPrompt('Answer in one short sentence.');
function show(string $label, ?Message $reply): void
{
    $tokens = $reply?->getUsage()?->inputTokens;
    echo "{$label}: {$reply?->getContent()}" . PHP_EOL;
    echo "    input tokens: {$tokens}" . PHP_EOL;
}
$hello = new UserMessage('Hi, my name is Valerio.');
$answer = $model->chat($hello)->message();
show('Call 1', $answer);
$question = new UserMessage('What is my name?');
show('Call 2, alone', $model->chat($question)->message());
$history = [$hello, $answer, $question];
show('Call 3, history', $model->chat(...$history)->message());
$agent = Agent::make()
    ->setAiProvider($model)
    ->setInstructions('Answer in one short sentence.')
    ->setThreadId('memory');
$turns = [
    'Hi, my name is Valerio.',
    'What is my name?',
    'How many letters does it have?',
];
foreach ($turns as $i => $turn) {
    $reply = $agent->chat(new UserMessage($turn))->getMessage();
    show('Agent turn ' . ($i + 1), $reply);
}
