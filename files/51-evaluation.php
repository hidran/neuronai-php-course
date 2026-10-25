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
} elseif ($mode === 'errors') {
    $fake = new FakeAIProvider(
        new AssistantMessage('No useful content.')
    );
    $results = new EvaluatorRunner()
        ->run(new TeachingEvaluator($fake, TeachingCases::basic()));
    foreach ($results->getResults() as $result) {
        echo $result->getInput()['id'] . ': ' . status($result)
            . PHP_EOL;
        if ($result->hasError()) {
            echo '  ' . $result->getError() . PHP_EOL;
        }
    }
    echo 'A missing answer is an ERROR, a wrong one is a FAIL.'
        . PHP_EOL;
} elseif ($mode === 'report') {
    $json = file_get_contents($storage . '/cli-results.json');
    $report = json_decode($json, associative: true,
        flags: JSON_THROW_ON_ERROR);
    foreach ($report['results'] as $case) {
        echo $case['input']['id'] . ': ' . $case['output'] . PHP_EOL;
    }
    echo 'Saved outputs inspected.' . PHP_EOL;
} elseif ($mode === 'compare') {
    $provider = new ProviderFactory($settings)->create();
    $dataset = TeachingCases::expanded();
    $baseline = TeachingAgent::make($provider)->getInstructions();
    $candidate = new SystemMessage($baseline->getContent()
        . ' Use at most three sentences.');
    $summary = [];
    foreach (['baseline' => $baseline, 'candidate' => $candidate]
        as $label => $prompt) {
        $runner = new EvaluatorRunner()->withCache(
            new FileEvaluationCache("{$storage}/cache/{$label}"));
        $results = $runner->run(
            new TeachingEvaluator($provider, $dataset, $prompt), 3);
        foreach ($results->getResults() as $result) {
            echo "{$label} / " . $result->getInput()['id'] . ': '
                . status($result) . PHP_EOL;
        }
        $summary[$label] = [
            'prompt' => $prompt->getContent(),
            'passed' => $results->getPassedCount(),
            'total' => $results->getTotalCount(),
            'cached' => $results->getCachedRunCount(),
        ];
    }
    $summary['context'] = [
        'provider' => $settings->provider->value,
        'model' => $settings->model,
        'dataset_sha256' => hash('sha256', json_encode($dataset)),
        'time_utc' => gmdate('c'),
    ];
    file_put_contents($storage . '/comparison.json',
        json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo 'Comparison saved with prompts and model.' . PHP_EOL;
} else {
    throw new InvalidArgumentException('Unknown demo mode.');
}
