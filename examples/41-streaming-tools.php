<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\ServerAgent;
use App\Streaming\StreamProgress;
use App\Streaming\ToolStreamConsumer;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$view = new StreamProgress(diagnostic: true);
$stream = ServerAgent::make()->setThreadId('server-fixture')
    ->stream(new UserMessage('What is the training server address?'));
$state = ToolStreamConsumer::consume($stream, $view);
exit($state === null ? 1 : 0);
