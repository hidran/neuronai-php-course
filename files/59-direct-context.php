<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$sheet = (string) file_get_contents(
    dirname(__DIR__) . '/fixtures/knowledge/desk-lamp.txt');
$question = 'Can I replace the bulb in the Cedar Desk Lamp?';
$agent = Agent::make()->setAiProvider($provider)
    ->setInstructions(new SystemMessage(
        'Answer only from the reference text. If it does not say, '
        . 'answer that it does not say. Use one short sentence.'))
    ->setContext(new TextContent("Reference:\n{$sheet}"))
    ->setThreadId('direct-context');
$start = hrtime(true);
$state = $agent->chat(new UserMessage($question));
$ms = intdiv(hrtime(true) - $start, 1_000_000);
$usage = $state->getMessage()?->getUsage();
echo "Question: {$question}" . PHP_EOL;
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
echo 'Reference size: ' . strlen($sheet) . ' characters' . PHP_EOL;
echo 'Input tokens: ' . ($usage?->inputTokens ?? 0) . PHP_EOL;
echo "Latency: {$ms} ms" . PHP_EOL;
echo 'Direct context run complete.' . PHP_EOL;
