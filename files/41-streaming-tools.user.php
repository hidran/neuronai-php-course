<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\ServerAgent;
use App\Config\ProviderFactory;
use App\Streaming\StreamProgress;
use App\Streaming\ToolStreamConsumer;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$view = new StreamProgress(diagnostic: false);
$stream = ServerAgent::make($provider)->setThreadId('server-fixture')
    ->stream(new UserMessage('What is the training server address?'));
$state = new ToolStreamConsumer($view)->consume($stream);
exit($state === null ? 1 : 0);
