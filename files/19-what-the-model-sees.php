<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\PackageVersionTool;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\Anthropic\ToolMapper as AnthropicMapper;
use NeuronAI\Providers\Gemini\ToolMapper as GeminiMapper;
use NeuronAI\Providers\OpenAI\ToolMapper as OpenAIMapper;
require __DIR__ . '/bootstrap.php';
$tools = [new PackageVersionTool()];
$mappers = [
    'anthropic' => new AnthropicMapper(),
    'openai' => new OpenAIMapper(),
    'gemini' => new GeminiMapper(),
];
$question = new UserMessage(
    'Which neuron-core/neuron-ai version is installed here?',
);
foreach ($mappers as $name => $mapper) {
    $wire = $mapper->map($tools);
    $json = json_encode($wire, JSON_UNESCAPED_SLASHES);
    echo "{$name} sends " . strlen($json) . ' bytes:' . PHP_EOL;
    echo $json . PHP_EOL;
    $env = ['NEURON_PROVIDER' => $name] + $_ENV;
    $provider = new ProviderFactory(Settings::fromEnv($env))->create();
    $provider->systemPrompt('Answer in one short sentence.');
    $bare = $provider->chat($question)->message();
    $provider->setTools($tools);
    $armed = $provider->chat($question)->message();
    $without = $bare->getUsage()?->inputTokens;
    $with = $armed->getUsage()?->inputTokens;
    $kind = $armed instanceof ToolCallMessage ? 'tool call' : 'text';
    echo "{$name} input tokens: {$without} without tools,"
        . " {$with} with ours, answer: {$kind}" . PHP_EOL;
}
