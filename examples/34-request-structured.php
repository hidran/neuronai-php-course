<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\PersonExtractor;
use App\Config\ProviderFactory;
use App\Output\Person;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$agent = PersonExtractor::make($provider)->setThreadId('person');
$message = new UserMessage(
    'I am Ada Turner, I am 32, and my favorite food is pizza.'
);
$person = $agent->structured($message, Person::class);
echo 'Returned type: ' . $person::class . PHP_EOL;
echo 'Name: ' . $person->name . PHP_EOL;
echo 'Food: ' . $person->preference . PHP_EOL;
echo 'Age: ' . ($person->age ?? 'not stated') . PHP_EOL;
echo 'Extraction complete.' . PHP_EOL;
