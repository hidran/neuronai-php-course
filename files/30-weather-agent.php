<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Tools\WeatherTool;
use Dotenv\Dotenv;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ToolRunsExceededException;
use NeuronAI\HttpClient\Curl\CurlHttpClient;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Tools\Toolkits\Calculator\CalculatorToolkit;
use NeuronAI\Tools\Toolkits\Calculator\EvaluateTool;
use NeuronAI\Tools\Toolkits\Calculator\MeanTool;
use NeuronAI\Tools\ToolOutput;
Dotenv::createImmutable(dirname(__DIR__))->load();
$http = new CurlHttpClient(timeout: 10.0);
$weather = WeatherTool::make($http)->setMaxRuns(4);
$math = CalculatorToolkit::make()
    ->only([EvaluateTool::class, MeanTool::class]);
$prompt = new SystemPrompt(
    background: ['You are a weather assistant.'],
    steps: [
        'Get every temperature from the weather tool.',
        'Do every calculation with the calculator tools.',
        'If any city is unavailable, say so. Do not average'
            . ' partial data, invent values, or retry.',
    ],
    output: ['Two short sentences, temperatures in Celsius.'],
);
$agent = Agent::make()
    ->setAiProvider(new Anthropic(
        key: $_ENV['ANTHROPIC_KEY'],
        model: $_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-5-5',
    ))
    ->setInstructions((string) $prompt)
    ->addTool([$weather, $math])
    ->toolMaxRuns(3)
    ->setThreadId('weather-lab');
$cities = array_slice($argv, 1) ?: ['Turin', 'Milan'];
$question = 'What is the average temperature now between '
    . implode(' and ', $cities) . '?';
echo "Question: {$question}" . PHP_EOL;
try {
    $state = $agent->chat(new UserMessage($question));
} catch (ToolRunsExceededException $e) {
    fwrite(STDERR, "Guard tripped: {$e->getMessage()}" . PHP_EOL);
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, "Request failed: {$e->getMessage()}" . PHP_EOL);
    exit(1);
}
$round = 0;
foreach ($state->getSteps() as $step) {
    if ($step instanceof ToolCallMessage) {
        $round++;
        foreach ($step->getToolCalls() as $call) {
            $inputs = json_encode($call->getInputs());
            echo "Round {$round}: {$call->getName()} {$inputs}"
                . PHP_EOL;
        }
    }
    if ($step instanceof ToolResultMessage) {
        foreach ($step->getToolCalls() as $call) {
            $result = $call->getResult();
            $failed = $result instanceof ToolOutput
                && $result->isError();
            $label = $failed ? 'Error' : 'Result';
            echo "  {$label}: {$result}" . PHP_EOL;
        }
    }
}
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
foreach ($state->getToolRuns() as $tool => $count) {
    echo "Runs of {$tool}: {$count}" . PHP_EOL;
}
echo "Weather lab complete." . PHP_EOL;
