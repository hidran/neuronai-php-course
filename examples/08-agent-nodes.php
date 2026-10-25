<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Observability\WorkflowNodeStart;
Dotenv::createImmutable(dirname(__DIR__))->load();
$provider = new ProviderFactory(Settings::fromEnv($_ENV))->create();
$question = $argv[1]
    ?? 'Which neuron-core/neuron-ai version is installed?';
$agent = HelloAgent::make($provider)->setThreadId('nodes');
$agent->subscribe(
    WorkflowNodeStart::class,
    function (WorkflowNodeStart $event): void {
        echo 'Node: ' . $event->node . PHP_EOL;
    },
);
$state = $agent->chat(new UserMessage($question));
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
echo 'State: ' . $state::class . ' extends '
    . get_parent_class($state) . PHP_EOL;
