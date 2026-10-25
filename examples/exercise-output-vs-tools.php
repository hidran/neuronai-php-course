<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\PersonExtractor;
use App\Config\ProviderFactory;
use App\HelloAgent;
use App\Output\PackageReport;
use App\PackageVersionTool;
use Composer\InstalledVersions;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$person = PersonExtractor::make($provider)->setThreadId('exercise-data')
    ->structured(new UserMessage(
        'My name is Mia Chen. My favorite food is sushi.'
    ));
if ($person->age !== null || $person->name !== 'Mia Chen') {
    throw new RuntimeException('Extraction facts differ.');
}
echo 'Data type: ' . $person::class . PHP_EOL;
echo 'Age: not stated' . PHP_EOL;
$state = HelloAgent::make($provider)->setThreadId('exercise-tool')
    ->chat(new UserMessage(
        'Use get_package_version to check vlucas/phpdotenv. '
        . 'Tell me the installed version.'
    ));
$runs = $state->getToolRuns('get_package_version');
if ($runs < 1) {
    throw new RuntimeException('The lookup tool did not run.');
}
echo 'Chat type: ' . $state::class . PHP_EOL;
echo 'Lookup runs: ' . $runs . PHP_EOL;
echo $state->getMessage()?->getContent() . PHP_EOL;
$report = Agent::make()->setAiProvider($provider)
    ->setInstructions(new SystemMessage(
        'Call get_package_version first, then fill the report.'
    ))
    ->addTool(new PackageVersionTool())
    ->setThreadId('exercise-report')
    ->structured(new UserMessage(
        'Report on the package vlucas/phpdotenv.'
    ), PackageReport::class);
$installed = InstalledVersions::getPrettyVersion('vlucas/phpdotenv');
if (!str_contains($report->version, ltrim((string) $installed, 'v'))) {
    throw new RuntimeException('Reported version is not the tool\'s.');
}
echo 'Report type: ' . $report::class . PHP_EOL;
echo 'Reported version: ' . $report->version . PHP_EOL;
echo 'Exercise complete.' . PHP_EOL;
