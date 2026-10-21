<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ProviderException;
use NeuronAI\Tools\ProviderTool;
Dotenv::createImmutable(dirname(__DIR__))->load();
$provider = $argv[1] ?? 'anthropic';
echo "Provider: {$provider}" . PHP_EOL;
$search = ProviderTool::make(
    type: 'web_search_20250305',
    name: 'web_search',
)->setOptions(['max_uses' => 3]);
$agent = AssistantAgent::make()
    ->useProvider($provider)
    ->addTool($search)
    ->setThreadId('provider-tools');
$question = 'Search the web: what is the latest stable PHP'
    . ' release, and when did it come out?';
try {
    $state = $agent->chat(new UserMessage($question));
} catch (ProviderException $e) {
    echo "Refused: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}
$answer = $state->getMessage();
echo $answer->getContent() . PHP_EOL;
$sources = [];
foreach ($answer->getMetadata('citations') ?? [] as $citation) {
    $sources[$citation->source] = $citation->title;
}
echo 'Sources: ' . count($sources) . PHP_EOL;
foreach ($sources as $url => $title) {
    echo "- {$title}: {$url}" . PHP_EOL;
}
$usage = $answer->getUsage();
echo "Input tokens: {$usage?->inputTokens}" . PHP_EOL;
