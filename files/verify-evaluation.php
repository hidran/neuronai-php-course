<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
// Self-contained: every class it loads is copied into this folder
// (prepare.sh compares the copies with their sources byte for byte).
foreach (['Provider', 'Settings', 'ProviderFactory', 'FixtureWeatherTool',
    'ObservedWeatherAgent', 'TeachingAgent', 'TeachingCases',
    'TeachingEvaluator', 'WeatherEvaluator'] as $class) {
    require __DIR__ . "/{$class}.php";
}
use App\Evaluators\Suite\TeachingEvaluator;
use App\Evaluators\Suite\WeatherEvaluator;
use App\Evaluators\TeachingCases;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Evaluation\Runner\EvaluatorRunner;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
$answer = 'Dependencies are passed from outside. Types constrain '
    . 'values. A namespace avoids collisions between names.';
$fake = new FakeAIProvider(new AssistantMessage($answer),
    new AssistantMessage($answer), new AssistantMessage($answer));
$results = new EvaluatorRunner()->run(new TeachingEvaluator($fake));
if ($results->getPassedCount() !== 3
    || $results->getTotalAssertionsPassed() !== 6
    || $fake->getCallCount() !== 3) {
    throw new RuntimeException('independent evaluation failed');
}
foreach ($fake->getRecorded() as $record) {
    if (count($record->messages) !== 1) {
        throw new RuntimeException('conversation leaked across cases');
    }
}
$prompt = new SystemMessage('Answer in Italian.');
$fake = new FakeAIProvider(new AssistantMessage('Le dipendenze...'));
new EvaluatorRunner()->run(new TeachingEvaluator($fake,
    [TeachingCases::basic()[0]], $prompt));
if ($fake->getRecorded()[0]->systemPrompt?->getContent() !== 'Answer in Italian.') {
    throw new RuntimeException('injected prompt not used');
}
$outage = new EvaluatorRunner()->run(new TeachingEvaluator(
    new FakeAIProvider(), TeachingCases::basic()));
foreach ($outage->getResults() as $result) {
    if (!$result->hasError() || $result->hasAssertionFailures()) {
        throw new RuntimeException('provider outage became a bad score');
    }
}
$fake = new FakeAIProvider(
    new ToolCallMessage(null, [new ToolCall('fixture_temperature', 'c1',
        ['city' => 'Turin'])]),
    new AssistantMessage('Turin is at 18 Celsius.'),
    new AssistantMessage('Milan is around 22 Celsius.'),
);
[$turin, $milan] = new EvaluatorRunner()->run(new WeatherEvaluator($fake))
    ->getResults();
if (!$turin->isPassed() || $milan->isPassed()
    || $milan->getAssertionsFailed() !== 1) {
    throw new RuntimeException('trajectory assertion misjudged');
}
echo 'EVALUATION OK' . PHP_EOL;
