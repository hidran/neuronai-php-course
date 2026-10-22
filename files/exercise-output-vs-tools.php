<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\PersonExtractor;
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$person = PersonExtractor::make()->setThreadId('exercise-data')
    ->structured(new UserMessage(
        'My name is Mia Chen. My favorite food is sushi.'
    ));
if ($person->age !== null || $person->name !== 'Mia Chen') {
    throw new RuntimeException('Extraction facts differ.');
}
echo 'Data type: ' . $person::class . PHP_EOL;
echo 'Age: not stated' . PHP_EOL;
$state = HelloAgent::make()->setThreadId('exercise-tool')
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
echo 'Exercise complete.' . PHP_EOL;
