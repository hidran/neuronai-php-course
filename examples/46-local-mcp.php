<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\OfficeHoursAgent;
use App\Mcp\OfficeHoursConnection;
use App\Streaming\StreamProgress;
use App\Streaming\ToolStreamConsumer;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
OfficeHoursConnection::discover();
if (($argv[1] ?? '') === '--discover') {
    exit;
}
Dotenv::createImmutable(dirname(__DIR__))->load();
$view = new StreamProgress(diagnostic: true);
$stream = OfficeHoursAgent::make()->setThreadId('office-demo')
    ->stream(new UserMessage('When is the north office open?'));
$state = ToolStreamConsumer::consume($stream, $view);
if ($state === null) {
    exit(1);
}
if (!in_array('get_office_hours', $view->completed(), true)) {
    throw new RuntimeException('The server capability did not run.');
}
$reply = $state->getMessage()?->getContent() ?? '';
if (!str_contains($reply, '09:00') || !str_contains($reply, '17:00')
    || !str_contains($reply, 'Europe/Rome')) {
    throw new RuntimeException('The answer missed server facts.');
}
echo 'Local MCP ' . 'verified.' . PHP_EOL;
