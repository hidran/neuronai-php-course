<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Evaluators\Suite\WeatherEvaluator;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Evaluation\Assertions\MatchesRegex;
use NeuronAI\Evaluation\Assertions\StringContains;
use NeuronAI\Evaluation\Runner\EvaluatorRunner;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
$good = 'Dependency injection receives dependencies from outside.';
$false = 'Dependency injection means never accepting dependencies.';
$cheap = new StringContains('dependencies');
$stronger = new MatchesRegex('/receives dependencies from outside/i');
if (!$cheap->evaluate($good)->passed
    || !$cheap->evaluate($false)->passed
    || !$stronger->evaluate($good)->passed
    || $stronger->evaluate($false)->passed) {
    throw new RuntimeException('Calibration controls failed.');
}
echo 'Positive example accepted; false positive rejected.' . PHP_EOL;
echo 'This wording rule still rejects valid paraphrases.' . PHP_EOL;
$fake = new FakeAIProvider(
    new ToolCallMessage(null, [
        new ToolCall('fixture_temperature', 'c1', ['city' => 'Turin']),
    ]),
    new AssistantMessage('Turin is at 18 Celsius.'),
    new AssistantMessage('Milan is probably around 22 Celsius.'),
);
$results = new EvaluatorRunner()->run(new WeatherEvaluator($fake));
[$turin, $milan] = $results->getResults();
if (!$turin->isPassed() || $milan->isPassed()
    || $milan->getAssertionsFailed() !== 1) {
    throw new RuntimeException('Trajectory assertions misjudged a run.');
}
echo 'A guessed answer fails the tool trajectory check.' . PHP_EOL;
echo 'Evaluation exercise passed.' . PHP_EOL;
