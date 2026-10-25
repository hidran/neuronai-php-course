<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$settings = Settings::fromEnv($_ENV);
$provider = new ProviderFactory($settings)->create();
$question = $argv[1]
    ?? 'Which neuron-core/neuron-ai version is installed here?';
$state = HelloAgent::make($provider)
    ->toolMaxRuns(3)
    ->setThreadId('first-tool')
    ->chat(new UserMessage($question));
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
foreach ($state->getSteps() as $i => $step) {
    echo 'Step ' . ($i + 1) . ': ' . $step::class . PHP_EOL;
    if ($step instanceof ToolResultMessage) {
        foreach ($step->getToolCalls() as $call) {
            echo '  Tool result: ' . $call->getResult() . PHP_EOL;
        }
    }
}
