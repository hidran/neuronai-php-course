<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\PersonExtractor;
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$person = PersonExtractor::make()->setThreadId('final-data')
    ->structured(new UserMessage(
        'I am Ada Turner, 32. My favorite food is pizza.'
    ));
echo 'Structured type: ' . $person::class . PHP_EOL;
echo 'Extracted name: ' . $person->name . PHP_EOL;
echo 'No application tool attached to this extractor.' . PHP_EOL;
$state = HelloAgent::make()->setThreadId('tool-request')
    ->chat(new UserMessage(
        'Use get_package_version to check vlucas/phpdotenv. '
        . 'Tell me the installed version.'
    ));
echo 'Chat type: ' . $state::class . PHP_EOL;
echo $state->getMessage()?->getContent() . PHP_EOL;
echo 'Comparison complete.' . PHP_EOL;
