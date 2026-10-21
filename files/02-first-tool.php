<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$question = $argv[1]
    ?? 'Which neuron-core/neuron-ai version is installed here?';
$state = HelloAgent::make()
    ->setThreadId('first-tool')
    ->chat(new UserMessage($question));
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
foreach ($state->getSteps() as $i => $step) {
    echo 'Step ' . ($i + 1) . ': ' . $step::class . PHP_EOL;
}
