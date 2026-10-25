<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\TeachingAgent;
use App\Config\ProviderFactory;
use App\Evaluators\Suite\TeachingEvaluator;
use App\Evaluators\TeachingCases;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Evaluation\Cache\FileEvaluationCache;
use NeuronAI\Evaluation\Runner\EvaluatorRunner;
use NeuronAI\Evaluation\Runner\EvaluatorResult;
use NeuronAI\Testing\FakeAIProvider;
$settings = require __DIR__ . '/bootstrap.php';
$root = dirname(__DIR__);
$storage = $root . '/storage/evaluation';
function status(EvaluatorResult $result): string
{
    return $result->hasError() ? 'ERROR'
        : ($result->isPassed() ? 'PASS' : 'FAIL');
}
$mode = $argv[1] ?? 'metrics';
if ($mode === 'metrics') {
    $case = TeachingCases::basic()[0];
    $bad = 'Dependencies must always be hard coded inside a class. '
        . 'Dependency injection means never accepting collaborators.';
    $answers = ['false positive' => $bad,
        'negative control' => 'Not relevant.'];
    foreach ($answers as $label => $answer) {
        $fake = new FakeAIProvider(new AssistantMessage($answer));
        $results = new EvaluatorRunner()
            ->run(new TeachingEvaluator($fake, [$case]));
        $result = $results->getResults()[0];
        echo "{$label}: " . status($result) . ' ('
            . $result->getAssertionsPassed() . ' of '
            . $result->getTotalAssertions() . ' checks)' . PHP_EOL;
    }
    echo 'Cheap checks can pass a wrong answer.' . PHP_EOL;
} else {
    throw new InvalidArgumentException('Unknown demo mode.');
}
