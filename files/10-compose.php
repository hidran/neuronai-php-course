<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\PackageVersionTool;
use Dotenv\Dotenv;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\Anthropic\Anthropic;
Dotenv::createImmutable(dirname(__DIR__))->load();
function ask(Agent $agent, string $question): void
{
    $state = $agent->setThreadId('compose')
        ->chat(new UserMessage($question));
    echo $agent::class . ': '
        . $state->getMessage()?->getContent() . PHP_EOL;
}
$composed = Agent::make()
    ->setAiProvider(new Anthropic(
        key: $_ENV['ANTHROPIC_KEY'],
        model: $_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-5-5',
    ))
    ->setInstructions('You are a terse PHP expert. One sentence.');
ask($composed, 'What is a PHP trait?');
$tuned = AssistantAgent::make()->addTool(new PackageVersionTool());
ask($tuned, 'Which neuron-core/neuron-ai version is installed?');
