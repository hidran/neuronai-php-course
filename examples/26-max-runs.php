<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Config\ProviderFactory;
use App\PackageVersionTool;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ToolRunsExceededException;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$limit = (int) ($argv[1] ?? 10);
echo "Limit: {$limit}" . PHP_EOL;
$question = 'Which versions do we have of vlucas/phpdotenv,'
    . ' phpoption/phpoption, psr/event-dispatcher'
    . ' and neuron-core/neuron-ai?';
$agent = AssistantAgent::make($provider)
    ->addTool(new PackageVersionTool())
    ->toolMaxRuns($limit)
    ->setThreadId('max-runs');
try {
    $state = $agent->chat(new UserMessage($question));
    echo $state->getMessage()?->getContent() . PHP_EOL;
    foreach ($state->getToolRuns() as $key => $count) {
        echo "Runs of {$key}: {$count}" . PHP_EOL;
    }
} catch (ToolRunsExceededException $e) {
    echo "Guard tripped: {$e->getMessage()}" . PHP_EOL;
}
