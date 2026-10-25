<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$settings = Settings::fromEnv($_ENV);
$provider = new ProviderFactory($settings)->create();
$agent = HelloAgent::make($provider)->setThreadId('anatomy');
echo 'Model: ' . $settings->model . PHP_EOL;
echo 'System: ' . $agent->getInstructions()->getContent() . PHP_EOL;
$flags = JSON_UNESCAPED_SLASHES;
foreach ($agent->getTools() as $tool) {
    echo "Tool: {$tool->getName()}: {$tool->getDescription()}"
        . PHP_EOL;
    $schema = $tool->getInputSchema();
    echo json_encode($schema, $flags | JSON_PRETTY_PRINT) . PHP_EOL;
}
$question = 'Which neuron-core/neuron-ai version is installed here?';
$state = $agent->chat(new UserMessage($question));
foreach ($state->getSteps() as $i => $step) {
    $text = $step->getContent() ?? '';
    if ($step instanceof ToolCallMessage) {
        foreach ($step->getToolCalls() as $call) {
            $text .= "call {$call->getName()} "
                . json_encode($call->getInputs(), $flags);
        }
    }
    if ($step instanceof ToolResultMessage) {
        foreach ($step->getToolCalls() as $call) {
            $text .= "result {$call->getResult()}";
        }
    }
    echo ($i + 1) . " {$step->getRole()}: {$text}" . PHP_EOL;
    if ($step instanceof AssistantMessage) {
        $usage = $step->getUsage();
        echo "   stop: {$step->stopReason()}"
            . ", input tokens: {$usage?->inputTokens}"
            . ", output tokens: {$usage?->outputTokens}" . PHP_EOL;
    }
}
