<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\WeatherAgent;
use App\Config\ProviderFactory;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\NeuronException;
use NeuronAI\Exceptions\ToolRunsExceededException;
use NeuronAI\HttpClient\Curl\CurlHttpClient;
use NeuronAI\Tools\ToolOutput;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$http = new CurlHttpClient(timeout: 10.0);
$agent = WeatherAgent::make($provider, $http)
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
} catch (NeuronException $e) {
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
