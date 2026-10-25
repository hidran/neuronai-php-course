<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Config\ProviderFactory;
use App\PackageVersionTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
function ask(Agent $agent, string $question): void
{
    $state = $agent->setThreadId('compose')
        ->chat(new UserMessage($question));
    echo $agent::class . ': '
        . $state->getMessage()?->getContent() . PHP_EOL;
}
$composed = Agent::make()
    ->setAiProvider($provider)
    ->setInstructions(new SystemMessage(
        'You are a terse PHP expert. One sentence.'
    ));
ask($composed, 'What is a PHP trait?');
$tuned = AssistantAgent::make($provider)
    ->addTool(new PackageVersionTool());
ask($tuned, 'Which neuron-core/neuron-ai version is installed?');
