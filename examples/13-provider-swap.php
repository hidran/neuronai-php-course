<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Config\ProviderFactory;
use App\PackageVersionTool;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$agent = AssistantAgent::make($provider)
    ->addTool(new PackageVersionTool())
    ->setThreadId('swap');
$start = hrtime(true);
$state = $agent->chat(new UserMessage(
    'Which vlucas/phpdotenv version is installed here?'
));
$ms = intdiv(hrtime(true) - $start, 1_000_000);
echo $state->getMessage()?->getContent() . PHP_EOL;
echo "Answered by: {$settings->provider->value}, "
    . "{$settings->model}, {$ms} ms" . PHP_EOL;
