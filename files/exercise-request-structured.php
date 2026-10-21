<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\PersonExtractor;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$message = new UserMessage(
    'My name is Mia Chen. My favorite food is sushi.'
);
$person = PersonExtractor::make()->setThreadId('exercise-data')
    ->structured($message);
if ($person->age !== null) {
    throw new RuntimeException('An unstated age must stay null.');
}
echo 'Structured type: ' . $person::class . PHP_EOL;
echo 'Name: ' . $person->name . PHP_EOL;
echo 'Food: ' . $person->preference . PHP_EOL;
echo 'Age: not stated' . PHP_EOL;
$state = PersonExtractor::make()->setThreadId('exercise-chat')
    ->chat($message);
echo 'Chat type: ' . $state::class . PHP_EOL;
echo 'Exercise complete.' . PHP_EOL;
