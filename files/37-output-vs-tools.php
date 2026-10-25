<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\PersonExtractor;
use App\Config\ProviderFactory;
use App\HelloAgent;
use App\Output\PackageReport;
use App\PackageVersionTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$person = PersonExtractor::make($provider)->setThreadId('final-data')
    ->structured(new UserMessage(
        'I am Ada Turner, 32. My favorite food is pizza.'
    ));
echo 'Structured type: ' . $person::class . PHP_EOL;
echo 'Extracted name: ' . $person->name . PHP_EOL;
echo 'No application tool attached to this extractor.' . PHP_EOL;
$state = HelloAgent::make($provider)->setThreadId('tool-request')
    ->chat(new UserMessage(
        'Use get_package_version to check vlucas/phpdotenv. '
        . 'Tell me the installed version.'
    ));
echo 'Chat type: ' . $state::class . PHP_EOL;
echo $state->getMessage()?->getContent() . PHP_EOL;
$report = Agent::make()->setAiProvider($provider)
    ->setInstructions(new SystemMessage(
        'Call get_package_version first, then fill the report.'
    ))
    ->addTool(new PackageVersionTool())
    ->setThreadId('tool-then-report')
    ->structured(new UserMessage(
        'Report on the package vlucas/phpdotenv.'
    ), PackageReport::class);
echo 'Report type: ' . $report::class . PHP_EOL;
echo 'Reported version: ' . $report->version . PHP_EOL;
echo 'Summary: ' . $report->summary . PHP_EOL;
echo 'Comparison complete.' . PHP_EOL;
