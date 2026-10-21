<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\PackageVersionTool;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$agent = AssistantAgent::make()
    ->useProvider($argv[1] ?? 'anthropic')
    ->addTool(new PackageVersionTool())
    ->setThreadId('swap');
$start = hrtime(true);
$state = $agent->chat(new UserMessage(
    'Which vlucas/phpdotenv version is installed here?'
));
$ms = intdiv(hrtime(true) - $start, 1_000_000);
$provider = $agent->getProvider();
echo $state->getMessage()?->getContent() . PHP_EOL;
echo 'Answered by: ' . $provider::class . PHP_EOL;
echo "Model: {$provider->getModel()}, {$ms} ms" . PHP_EOL;
