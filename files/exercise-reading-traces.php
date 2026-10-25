<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Observability\TraceReport;
$base = dirname(__DIR__) . '/storage/trace-';
$reports = [];
foreach (['baseline', 'modified'] as $name) {
    $report = TraceReport::load($base . $name . '.json')->data;
    if (!$report['inferences'] || !$report['tools']
        || !$report['answer'] || $report['wall_ms'] <= 0) {
        throw new RuntimeException('Incomplete real run evidence.');
    }
    $offered = array_column($report['offered'], null, 'name');
    $weather = $offered['fixture_temperature']['schema'] ?? [];
    $mean = $offered['mean']['schema'] ?? [];
    if (($weather['properties']['city']['type'] ?? null) !== 'string'
        || !in_array('city', $weather['required'] ?? [], true)
        || ($mean['properties']['numbers']['type'] ?? null)
            !== 'array'
        || !in_array('numbers', $mean['required'] ?? [], true)) {
        throw new RuntimeException('Incomplete offered input schemas.');
    }
    $reports[$name] = $report;
    echo $name . ': ' . count($report['inferences'])
        . ' inferences, ' . count($report['tools']) . ' tools, '
        . $report['cost_usd'] . ' USD' . PHP_EOL;
}
if ($reports['baseline']['question']
        !== $reports['modified']['question']
    || $reports['baseline']['label'] === $reports['modified']['label']
    || $reports['baseline']['offered'][0]['description']
        === $reports['modified']['offered'][0]['description']) {
    throw new RuntimeException('Controlled comparison is invalid.');
}
if (array_column($reports['baseline']['offered'], 'schema', 'name')
    !== array_column($reports['modified']['offered'], 'schema', 'name')) {
    throw new RuntimeException('The input contracts changed too.');
}
echo 'Actual input schemas unchanged and complete.' . PHP_EOL;
echo 'Controlled trace comparison verified.' . PHP_EOL;
echo 'Behavioral failure is not required by this exercise.' . PHP_EOL;
